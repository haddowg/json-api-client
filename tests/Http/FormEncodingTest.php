<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Http;

use haddowg\JsonApiClient\Http\FilePart;
use haddowg\JsonApiClient\Http\FormEncoding;
use haddowg\JsonApiClient\Http\Request;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FormEncoding::class)]
#[CoversClass(FilePart::class)]
#[CoversClass(Request::class)]
final class FormEncodingTest extends TestCase
{
    public function testAMultipartBodySendsFieldsAndFilesUnderItsBoundary(): void
    {
        $body = FormEncoding::multipart([
            'title' => 'Hello',
            'count' => 3,
            'draft' => false,
            'cover' => new FilePart(Stream::create('PNGBYTES'), 'cover.png', 'image/png'),
        ], 'b0undary');

        self::assertSame(
            "--b0undary\r\n"
            . "Content-Disposition: form-data; name=\"title\"\r\n\r\nHello\r\n"
            . "--b0undary\r\n"
            . "Content-Disposition: form-data; name=\"count\"\r\n\r\n3\r\n"
            . "--b0undary\r\n"
            . "Content-Disposition: form-data; name=\"draft\"\r\n\r\n0\r\n"
            . "--b0undary\r\n"
            . "Content-Disposition: form-data; name=\"cover\"; filename=\"cover.png\"\r\n"
            . "Content-Type: image/png\r\n\r\nPNGBYTES\r\n"
            . "--b0undary--\r\n",
            $body,
        );
    }

    public function testANameOrFilenameCannotBreakOutOfItsHeader(): void
    {
        $body = FormEncoding::multipart([
            "a\"b\r\nX-Evil: 1" => new FilePart('x', "f\".txt\r\n", "text/plain\r\nX-Evil: 1"),
        ], 'b');

        self::assertStringContainsString('name="a%22b%0D%0AX-Evil: 1"; filename="f%22.txt%0D%0A"', $body);
        self::assertStringContainsString("Content-Type: text/plainX-Evil: 1\r\n", $body);
        self::assertStringNotContainsString("\r\nX-Evil", $body);
    }

    public function testAFileReadsFromTheStartOfAStreamAlreadyRead(): void
    {
        $stream = Stream::create('abc');
        $stream->getContents();

        self::assertSame('abc', (new FilePart($stream, 'a.txt'))->bytes());
    }

    public function testEachMultipartBodyGetsAFreshBoundary(): void
    {
        self::assertNotSame(FormEncoding::boundary(), FormEncoding::boundary());
        self::assertMatchesRegularExpression('/^jsonapi-[0-9a-f]{32}$/', FormEncoding::boundary());
    }

    public function testAUrlencodedBodyEncodesEveryPair(): void
    {
        self::assertSame('title=Hello+World&a%26b=1%3D2&draft=1&n=1.5', FormEncoding::urlencoded([
            'title' => 'Hello World',
            'a&b' => '1=2',
            'draft' => true,
            'n' => 1.5,
        ]));
    }

    public function testARequestCarriesTheBoundaryInItsContentType(): void
    {
        $request = Request::post('/articles/-actions/bulk-import')->withMultipartBody(['title' => 'x'], 'b0undary');

        self::assertSame('multipart/form-data; boundary=b0undary', $request->contentType);
        self::assertSame("--b0undary\r\nContent-Disposition: form-data; name=\"title\"\r\n\r\nx\r\n--b0undary--\r\n", $request->body);

        $generated = Request::post('/x')->withMultipartBody([]);
        self::assertIsString($generated->contentType);
        self::assertStringStartsWith('multipart/form-data; boundary=jsonapi-', $generated->contentType);
    }

    public function testARequestCarriesAFormBody(): void
    {
        $request = Request::post('/x')->withFormBody(['q' => 'a b']);

        self::assertSame('application/x-www-form-urlencoded', $request->contentType);
        self::assertSame('q=a+b', $request->body);
    }

    public function testFieldArraysAreCheckedBeforeTheyAreEncoded(): void
    {
        $file = new FilePart('x', 'x.txt');

        self::assertSame(['a' => 'b', 'f' => $file], FormEncoding::multipartFields(['a' => 'b', 'f' => $file]));
        self::assertSame(['n' => 1, 'b' => true], FormEncoding::formFields(['n' => 1, 'b' => true]));
    }

    public function testAMapIsNotAMultipartField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"tags" is array');

        FormEncoding::multipartFields(['tags' => ['a' => 'b']]);
    }

    public function testANestedListIsNotAMultipartListItem(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('an item of "tags" is array');

        FormEncoding::multipartFields(['tags' => [['a']]]);
    }

    public function testListFieldsAreChecked(): void
    {
        $file = new FilePart('x', 'x.txt');

        self::assertSame(['tags' => ['a', 1], 'files' => [$file]], FormEncoding::multipartFields(['tags' => ['a', 1], 'files' => [$file]]));
        self::assertSame(['tags' => ['a', true]], FormEncoding::formFields(['tags' => ['a', true]]));
    }

    public function testAFileIsNotAUrlencodedListItem(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FormEncoding::formFields(['f' => [new FilePart('x', 'x.txt')]]);
    }

    public function testAListSendsOneBracketedFieldPerItem(): void
    {
        self::assertSame('tags%5B%5D=a&tags%5B%5D=b&ids%5B%5D=1', FormEncoding::urlencoded([
            'tags' => ['a', 'b'],
            'ids[]' => [1],
            'none' => [],
        ]));

        $body = FormEncoding::multipart(['tags' => ['a', 'b'], 'files' => [new FilePart('x', 'x.txt')]], 'B');
        self::assertSame(
            "--B\r\nContent-Disposition: form-data; name=\"tags[]\"\r\n\r\na\r\n"
            . "--B\r\nContent-Disposition: form-data; name=\"tags[]\"\r\n\r\nb\r\n"
            . "--B\r\nContent-Disposition: form-data; name=\"files[]\"; filename=\"x.txt\"\r\nContent-Type: application/octet-stream\r\n\r\nx\r\n"
            . "--B--\r\n",
            $body,
        );
    }

    public function testAFileIsNotAUrlencodedField(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FormEncoding::formFields(['f' => new FilePart('x', 'x.txt')]);
    }

    public function testAListIsNotAFieldArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FormEncoding::formFields(['a']);
    }
}
