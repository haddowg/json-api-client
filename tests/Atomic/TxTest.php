<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Atomic;

use haddowg\JsonApiClient\Atomic\Created;
use haddowg\JsonApiClient\Atomic\Tx;
use haddowg\JsonApiClient\Atomic\Updated;
use haddowg\JsonApiClient\ClientOptions;
use haddowg\JsonApiClient\Errors\SourcePointer;
use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Exceptions\MissingRequiredMemberException;
use haddowg\JsonApiClient\Exceptions\ValidationFailed;
use haddowg\JsonApiClient\Http\Transport;
use haddowg\JsonApiClient\MediaType;
use haddowg\JsonApiClient\Resources\Fetcher;
use haddowg\JsonApiClient\Support\Coerce;
use haddowg\JsonApiClient\Support\Identifier;
use haddowg\JsonApiClient\Tests\Fixtures\Note;
use haddowg\JsonApiClient\Tests\Fixtures\NoteCreate;
use haddowg\JsonApiClient\Tests\Fixtures\NoteCreateBuilder;
use haddowg\JsonApiClient\Tests\Fixtures\NoteUpdate;
use haddowg\JsonApiClient\Tests\Fixtures\RecordingHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Tx::class)]
#[CoversClass(Created::class)]
#[CoversClass(Updated::class)]
#[CoversClass(Fetcher::class)]
#[CoversClass(Coerce::class)]
#[CoversClass(SourcePointer::class)]
final class TxTest extends TestCase
{
    private const string ATOMIC = 'application/vnd.api+json; ext="https://jsonapi.org/ext/atomic"';

    public function testABatchIsSentAsOneRequestAndItsHandlesReadTheResults(): void
    {
        $client = RecordingHttpClient::answering(200, self::results(
            ['data' => self::note('10', 'Draft')],
            ['data' => self::note('11', 'Reply'), 'meta' => ['revision' => 1]],
            ['data' => self::note('10', 'Final')],
            [],
        ));

        $fetcher = $this->fetcher($client);
        $returned = $fetcher->atomic('/operations', static function (Tx $tx): array {
            $draft = $tx->create(new NoteCreate('Draft'));
            $reply = $tx->create(new NoteCreate('Reply', parent: $draft));
            $final = $tx->update($draft, new NoteUpdate('Final'));
            $tx->delete(Identifier::of('notes', '3'));

            return [$draft, $reply, $final];
        });

        [$draft, $reply, $final] = $returned;

        $request = $client->lastRequest();
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://api.example.com/operations', (string) $request->getUri());
        self::assertSame(self::ATOMIC, $request->getHeaderLine('Content-Type'));
        self::assertSame(self::ATOMIC, $request->getHeaderLine('Accept'));
        self::assertSame(['atomic:operations' => [
            ['op' => 'add', 'data' => ['type' => 'notes', 'lid' => 'atomic-0', 'attributes' => ['title' => 'Draft']]],
            ['op' => 'add', 'data' => [
                'type' => 'notes',
                'lid' => 'atomic-1',
                'attributes' => ['title' => 'Reply'],
                'relationships' => ['parent' => ['data' => ['type' => 'notes', 'lid' => 'atomic-0']]],
            ]],
            ['op' => 'update', 'ref' => ['type' => 'notes', 'lid' => 'atomic-0'], 'data' => ['type' => 'notes', 'lid' => 'atomic-0', 'attributes' => ['title' => 'Final']]],
            ['op' => 'remove', 'ref' => ['type' => 'notes', 'id' => '3']],
        ]], \json_decode((string) $request->getBody(), true));

        self::assertSame('Draft', $draft->resource()->title());
        self::assertSame('10', $draft->id());
        self::assertSame('Reply', $reply->resource()->title());
        self::assertSame(['revision' => 1], $reply->meta());
        self::assertSame(1, $reply->opIndex);
        self::assertSame('Final', $final->resource()?->title());
        self::assertSame($draft->resource(), $draft->resource());
        self::assertSame($fetcher, $draft->resource()->fetcher());
    }

    public function testACreateCarryingAClientIdIsReferencedByIt(): void
    {
        $client = RecordingHttpClient::answering(200, self::results(
            ['data' => self::note('n-1', 'Draft')],
            ['data' => self::note('n-2', 'Reply')],
        ));

        $this->fetcher($client)->atomic('/operations', static function (Tx $tx): void {
            $draft = $tx->create(new NoteCreate('Draft', id: 'n-1'));
            self::assertNull($draft->lid);
            self::assertSame('n-1', $draft->id());
            $tx->create(new NoteCreate('Reply', id: 'n-2', parent: $draft));
        });

        self::assertSame([
            ['op' => 'add', 'data' => ['type' => 'notes', 'id' => 'n-1', 'attributes' => ['title' => 'Draft']]],
            ['op' => 'add', 'data' => [
                'type' => 'notes',
                'id' => 'n-2',
                'attributes' => ['title' => 'Reply'],
                'relationships' => ['parent' => ['data' => ['type' => 'notes', 'id' => 'n-1']]],
            ]],
        ], self::operations($client));
    }

    public function testABuilderIsBuiltWhenItIsRecorded(): void
    {
        $client = RecordingHttpClient::answering(200, self::results(['data' => self::note('1', 'Built')]));

        $note = $this->fetcher($client)->atomic('/operations', static fn(Tx $tx): Created => $tx->create((new NoteCreateBuilder())->title('Built')));

        self::assertSame('Built', $note->resource()->title());
    }

    public function testABuilderMissingARequiredMemberThrowsBeforeAnythingIsSent(): void
    {
        $client = new RecordingHttpClient();

        try {
            $this->fetcher($client)->atomic('/operations', static fn(Tx $tx): Created => $tx->create(new NoteCreateBuilder()));
            self::fail('Expected a missing-member exception.');
        } catch (MissingRequiredMemberException) {
            self::assertSame([], $client->requests);
        }
    }

    public function testABatchThatRecordsNothingSendsNothing(): void
    {
        $client = new RecordingHttpClient();

        $returned = $this->fetcher($client)->atomic('/operations', static fn(Tx $tx): string => 'nothing to do');

        self::assertSame('nothing to do', $returned);
        self::assertSame([], $client->requests);
    }

    public function testAResourceCanBeTargetedByTheResourceItself(): void
    {
        $client = RecordingHttpClient::answering(200, self::results([], []));
        $existing = Note::_hydrate(self::note('7', 'Old'), \haddowg\JsonApiClient\Resources\ResourceContext::of(['data' => self::note('7', 'Old')]));

        $this->fetcher($client)->atomic('/operations', static function (Tx $tx) use ($existing): void {
            $updated = $tx->update($existing, new NoteUpdate('New'));
            $tx->delete($existing);
            self::assertSame(0, $updated->opIndex);
        });

        self::assertSame([
            ['op' => 'update', 'ref' => ['type' => 'notes', 'id' => '7'], 'data' => ['type' => 'notes', 'id' => '7', 'attributes' => ['title' => 'New']]],
            ['op' => 'remove', 'ref' => ['type' => 'notes', 'id' => '7']],
        ], self::operations($client));
    }

    public function testAnUpdateAnsweredWithAnEmptyResultHasNoResource(): void
    {
        $client = RecordingHttpClient::answering(200, self::results([]));

        $updated = $this->fetcher($client)->atomic('/operations', static fn(Tx $tx): Updated => $tx->update(Identifier::of('notes', '1'), new NoteUpdate('Same')));

        self::assertNull($updated->resource());
        self::assertSame([], $updated->meta());
    }

    public function testANoContentAnswerResolvesEveryOperationEmpty(): void
    {
        $client = RecordingHttpClient::answering(204);

        $created = $this->fetcher($client)->atomic('/operations', static fn(Tx $tx): Created => $tx->create(new NoteCreate('Lost')));

        $this->expectException(MalformedDocument::class);
        $this->expectExceptionMessage('The result of atomic operation 0');
        $created->resource();
    }

    public function testAnAnswerWithTheWrongNumberOfResultsIsMalformed(): void
    {
        $client = RecordingHttpClient::answering(200, self::results([]));

        $this->expectException(MalformedDocument::class);
        $this->expectExceptionMessage('a list of 2 results');

        $this->fetcher($client)->atomic('/operations', static function (Tx $tx): void {
            $tx->delete(Identifier::of('notes', '1'));
            $tx->delete(Identifier::of('notes', '2'));
        });
    }

    public function testARejectedBatchBlamesTheOperationAndTheMember(): void
    {
        $client = RecordingHttpClient::answering(422, (string) \json_encode(['errors' => [
            ['status' => '422', 'title' => 'Invalid', 'source' => ['pointer' => '/atomic:operations/1/data/attributes/title']],
            ['status' => '422', 'title' => 'Invalid', 'source' => ['pointer' => '/atomic:operations/0']],
        ]]));

        $handles = [];

        try {
            $this->fetcher($client)->atomic('/operations', static function (Tx $tx) use (&$handles): void {
                $handles[] = $tx->create(new NoteCreate('Fine'));
                $handles[] = $tx->create(new NoteCreate(''));
            });
            self::fail('Expected the batch to be rejected.');
        } catch (ValidationFailed $e) {
            [$title, $whole] = $e->errors();
            self::assertSame(1, $title->opIndex);
            self::assertSame('title', $title->path);
            self::assertSame('/atomic:operations/1/data/attributes/title', $title->pointer());
            self::assertSame(0, $whole->opIndex);
            self::assertSame('_', $whole->path);
            self::assertSame(['title', '_'], \array_keys($e->byPath()));
        }

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('has not been committed');
        $handles[0]->resource();
    }

    public function testRecordingAfterTheBatchIsSentThrows(): void
    {
        $client = RecordingHttpClient::answering(200, self::results([]));
        $leaked = null;

        $this->fetcher($client)->atomic('/operations', static function (Tx $tx) use (&$leaked): void {
            $tx->delete(Identifier::of('notes', '1'));
            $leaked = $tx;
        });

        self::assertInstanceOf(Tx::class, $leaked);
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('already been sent');
        $leaked->delete(Identifier::of('notes', '2'));
    }

    public function testAResourceReadWithoutAClientHasNoFetcher(): void
    {
        $note = Note::_hydrate(self::note('1', 'Loose'), \haddowg\JsonApiClient\Resources\ResourceContext::of(['data' => self::note('1', 'Loose')]));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This "notes" resource was not read through a client');

        $note->fetcher();
    }

    public function testAnUpdateMustWriteTheTargetsType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('An update of "memos" cannot target a "notes" resource.');

        (new Tx())->update(Identifier::of('notes', '1'), new NoteUpdate('x', type: 'memos'));
    }

    private function fetcher(RecordingHttpClient $client): Fetcher
    {
        return new Fetcher(new Transport(new ClientOptions(
            baseUrl: 'https://api.example.com',
            transport: $client,
            requestFactory: new Psr17Factory(),
            streamFactory: new Psr17Factory(),
        )));
    }

    /**
     * @return array<string, mixed>
     */
    private static function note(string $id, string $title): array
    {
        return ['type' => 'notes', 'id' => $id, 'attributes' => ['title' => $title]];
    }

    /**
     * @param array<string, mixed> ...$results
     */
    private static function results(array ...$results): string
    {
        return (string) \json_encode([
            'jsonapi' => ['version' => '1.1', 'ext' => [MediaType::ATOMIC_OPERATIONS]],
            'atomic:results' => \array_map(static fn(array $result): array|\stdClass => $result === [] ? new \stdClass() : $result, $results),
        ]);
    }

    /**
     * @return mixed
     */
    private static function operations(RecordingHttpClient $client): mixed
    {
        $body = \json_decode((string) $client->lastRequest()->getBody(), true);

        return \is_array($body) ? $body['atomic:operations'] ?? null : null;
    }
}
