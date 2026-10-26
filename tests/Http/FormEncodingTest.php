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
}
