<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Validation;

use haddowg\JsonApiClient\ClientOptions;
use haddowg\JsonApiClient\Exceptions\SchemaViolation;
use haddowg\JsonApiClient\Http\Request;
use haddowg\JsonApiClient\Http\Transport;
use haddowg\JsonApiClient\Tests\Fixtures\RecordingHttpClient;
use haddowg\JsonApiClient\Validation\OpenApiValidator;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

#[CoversClass(OpenApiValidator::class)]
#[CoversClass(SchemaViolation::class)]
#[CoversClass(Transport::class)]
#[CoversClass(ClientOptions::class)]
final class OpenApiValidatorTest extends TestCase
{
    private const string JSON_API = 'application/vnd.api+json';

    public function testADocumentedResponseThatMatchesPasses(): void
    {
        self::validator()->validate(self::request('GET', '/notes/1'), '/notes/1', 200, self::JSON_API, self::note(['title' => 'Hi', 'pinned' => false]));

        $this->addToAssertionCount(1);
    }

    public function testAMemberOfTheWrongTypeIsAViolationAtItsPointer(): void
    {
        try {
            self::validator()->validate(self::request('GET', '/notes/1'), '/notes/1', 200, self::JSON_API, self::note(['title' => 'Hi', 'pinned' => 'yes']));
            self::fail('Expected a schema violation.');
        } catch (SchemaViolation $e) {
            self::assertArrayHasKey('/data/attributes/pinned', $e->violations);
            self::assertSame(200, $e->status);
            self::assertSame('/notes/1', $e->path);
            self::assertStringContainsString('The 200 response to GET /notes/1 does not match its documented schema', $e->getMessage());
        }
    }

    public function testAMissingAttributeIsAViolationUnlessTheRequestSentASparseFieldset(): void
    {
        $body = self::note(['title' => 'Hi']);

        self::validator()->validate(self::request('GET', '/notes/1?fields%5Bnotes%5D=title'), '/notes/1', 200, self::JSON_API, $body);

        $this->expectException(SchemaViolation::class);
        self::validator()->validate(self::request('GET', '/notes/1'), '/notes/1', 200, self::JSON_API, $body);
    }

    public function testASparseFieldsetStillChecksTheMembersThatArePresent(): void
    {
        $this->expectException(SchemaViolation::class);

        self::validator()->validate(self::request('GET', '/notes/1?fields[notes]=title'), '/notes/1', 200, self::JSON_API, self::note(['title' => '']));
    }

    public function testAResponseReferencedFromComponentsIsFoundThroughAStatusRange(): void
    {
        $this->expectException(SchemaViolation::class);

        self::validator()->validate(self::request('PATCH', '/notes/1'), '/notes/1', 200, self::JSON_API, '{"data":{"type":"memos","id":"1","attributes":{"title":"x","pinned":true}}}');
    }

    public function testALiteralPathWinsOverATemplateAndItsMediaTypeIsMatched(): void
    {
        self::validator()->validate(self::request('GET', '/notes/-actions/archive'), '/notes/-actions/archive', 200, 'text/csv; charset=utf-8', '"id,title"');

        $this->expectException(SchemaViolation::class);
        self::validator()->validate(self::request('GET', '/notes/-actions/archive'), '/notes/-actions/archive', 200, 'text/csv', '"title"');
    }

    public function testAMediaTypeWithParametersIsMatchedExactly(): void
    {
        $atomic = 'application/vnd.api+json; ext="https://jsonapi.org/ext/atomic"';

        self::validator()->validate(self::request('POST', '/operations'), '/operations', 200, $atomic, '{"atomic:results":[]}');

        $this->expectException(SchemaViolation::class);
        self::validator()->validate(self::request('POST', '/operations'), '/operations', 200, $atomic, '{"results":[]}');
    }

    public function testWhatTheDocumentDoesNotDescribeIsNotValidated(): void
    {
        $validator = self::validator();

        $validator->validate(self::request('GET', '/elsewhere'), '/elsewhere', 200, self::JSON_API, '{"anything":true}');
        $validator->validate(self::request('DELETE', '/notes/1'), '/notes/1', 200, self::JSON_API, '{"anything":true}');
        $validator->validate(self::request('GET', '/notes/1'), '/notes/1', 202, self::JSON_API, '{"anything":true}');
        $validator->validate(self::request('GET', '/notes/1'), '/notes/1', 200, 'application/json', '{"anything":true}');
        $validator->validate(self::request('GET', '/notes/1'), '/notes/1', 204, self::JSON_API, '');

        $this->addToAssertionCount(1);
    }

    public function testTheTransportValidatesRelativeToTheBaseUrlsPath(): void
    {
        $http = RecordingHttpClient::answering(200, self::note(['title' => 'Hi', 'pinned' => 3]));
        $transport = new Transport((new ClientOptions(
            baseUrl: 'https://api.example.com/v1',
            transport: $http,
            requestFactory: new Psr17Factory(),
            streamFactory: new Psr17Factory(),
        ))->withValidator(self::validator()));

        $this->expectException(SchemaViolation::class);
        $this->expectExceptionMessage('GET /notes/1');

        $transport->send(Request::get('/notes/1'));
    }

    public function testAnUnreadableDocumentIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OpenApiValidator::fromJson('[1, 2]');
    }

    private static function validator(): OpenApiValidator
    {
        return OpenApiValidator::fromFile(__DIR__ . '/../Fixtures/validation.openapi.json');
    }

    private static function request(string $method, string $uri): RequestInterface
    {
        return (new Psr17Factory())->createRequest($method, 'https://api.example.com' . $uri);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private static function note(array $attributes): string
    {
        return (string) \json_encode(['data' => ['type' => 'notes', 'id' => '1', 'attributes' => $attributes]]);
    }
}
