<?php
use PHPUnit\Framework\TestCase;
use unrealization\EMail;
use unrealization\MbRegEx;

/**
 * TarArchive test case.
 * @covers unrealization\EMail
 */
class EMailTest extends TestCase
{
	public function testEMail()
	{
		$mail = new EMail('test@example.com', 'Test Mail');
		$this->assertInstanceOf(EMail::class, $mail);

		$mail->addRecipient('test2@example.com');
		$mail->setTextBody('Test Mail');
		$this->assertRegExp(
			'@^'
			.'From: <test\@example\.com>\r\n'
			.'To: <test2\@example\.com>\r\n'
			.'Subject: Test Mail\r\n'
			.'User-Agent: PHP/unrealization/eMail\r\n'
			.'Date: \w{3}, \d{1,2} \w{3} \d{4} \d{2}:\d{2}:\d{2} [+-]\d{4}\r\n'
			.'MIME-Version: 1\.0\r\n'
			.'Content-Type: text/plain;\r\n\t'
			.'charset="ascii"\r\n'
			.'Content-Transfer-Encoding: quoted-printable\r\n'
			.'Content-Disposition: inline\r\n'
			.'\r\n'
			.'Test Mail\r\n'
			.'\r\n'
			.'$@'
			, $mail->composeMail()
		);

		$mail->addRecipient('test3@example.com', 'Cc');
		$mail->addRecipient('test4@example.com', 'Bcc');
		$this->assertRegExp(
			'@^'
			.'From: <test\@example\.com>\r\n'
			.'To: <test2\@example\.com>\r\n'
			.'Subject: Test Mail\r\n'
			.'User-Agent: PHP/unrealization/eMail\r\n'
			.'Date: \w{3}, \d{1,2} \w{3} \d{4} \d{2}:\d{2}:\d{2} [+-]\d{4}\r\n'
			.'Cc: <test3\@example\.com>\r\n'
			.'Bcc: <test4\@example\.com>\r\n'
			.'MIME-Version: 1\.0\r\n'
			.'Content-Type: text/plain;\r\n\t'
			.'charset="ascii"\r\n'
			.'Content-Transfer-Encoding: quoted-printable\r\n'
			.'Content-Disposition: inline\r\n'
			.'\r\n'
			.'Test Mail\r\n'
			.'\r\n'
			.'$@'
			, (string)$mail
		);

		$mail->setReplyTo('test5@example.com');
		$mail->setNotificationTo('test6@example.com');
		$this->assertRegExp(
			'@^'
			.'From: <test\@example\.com>\r\n'
			.'Reply-To: <test5\@example\.com>\r\n'
			.'Disposition-Notification-To: <test6\@example\.com>\r\n'
			.'To: <test2\@example\.com>\r\n'
			.'Subject: Test Mail\r\n'
			.'User-Agent: PHP/unrealization/eMail\r\n'
			.'Date: \w{3}, \d{1,2} \w{3} \d{4} \d{2}:\d{2}:\d{2} [+-]\d{4}\r\n'
			.'Cc: <test3\@example\.com>\r\n'
			.'Bcc: <test4\@example\.com>\r\n'
			.'MIME-Version: 1\.0\r\n'
			.'Content-Type: text/plain;\r\n\t'
			.'charset="ascii"\r\n'
			.'Content-Transfer-Encoding: quoted-printable\r\n'
			.'Content-Disposition: inline\r\n'
			.'\r\n'
			.'Test Mail\r\n'
			.'\r\n'
			.'$@'
			, $mail->composeMail()
		);

		$mail->setReplyTo(null);
		$mail->setNotificationTo(null);
		$mail->setHtmlBody('Test Mail<br/>');
		$this->assertRegExp(
			'@^'
			.'From: <test\@example\.com>\r\n'
			.'To: <test2\@example\.com>\r\n'
			.'Subject: Test Mail\r\n'
			.'User-Agent: PHP/unrealization/eMail\r\n'
			.'Date: \w{3}, \d{1,2} \w{3} \d{4} \d{2}:\d{2}:\d{2} [+-]\d{4}\r\n'
			.'Cc: <test3\@example\.com>\r\n'
			.'Bcc: <test4\@example\.com>\r\n'
			.'MIME-Version: 1\.0\r\n'
			.'Content-Type: multipart/alternative;\r\n\t'
			.'boundary="-{4}\w+"\r\n'
			.'\r\n'
			.'-{6}\w+\r\n'
			.'Content-Type: text/plain;\r\n\t'
			.'charset="ascii"\r\n'
			.'Content-Transfer-Encoding: quoted-printable\r\n'
			.'Content-Disposition: inline\r\n'
			.'\r\n'
			.'Test Mail\r\n'
			.'\r\n'
			.'\r\n'
			.'-{6}\w+\r\n'
			.'Content-Type: text/html;\r\n\t'
			.'charset="ascii"\r\n'
			.'Content-Transfer-Encoding: quoted-printable\r\n'
			.'Content-Disposition: inline\r\n'
			.'\r\n'
			.'Test Mail<br/>\r\n'
			.'\r\n'
			.'\r\n'
			.'-{6}\w+--'
			.'$@'
			, $mail->composeMail()
		);

		$mail->addAttachmentData('test.jpg', 'abc', true);
		$mail->setHtmlBody('<img src="test.jpg"/>');
		$this->assertRegExp(
			'@^'
			.'From: <test\@example\.com>\r\n'
			.'To: <test2\@example\.com>\r\n'
			.'Subject: Test Mail\r\n'
			.'User-Agent: PHP/unrealization/eMail\r\n'
			.'Date: \w{3}, \d{1,2} \w{3} \d{4} \d{2}:\d{2}:\d{2} [+-]\d{4}\r\n'
			.'Content-Type: multipart/mixed;\r\n\t'
			.'boundary="-{4}\w+"\r\n'
			.'Cc: <test3\@example\.com>\r\n'
			.'Bcc: <test4\@example\.com>\r\n'
			.'MIME-Version: 1\.0\r\n'
			.'\r\n'
			.'-{6}\w+\r\n'
			.'Content-Type: multipart/alternative;\r\n\t'
			.'boundary="-{4}\w+"\r\n'
			.'\r\n'
			.'-{6}\w+\r\n'
			.'Content-Type: text/plain;\r\n\t'
			.'charset="ascii"\r\n'
			.'Content-Transfer-Encoding: quoted-printable\r\n'
			.'Content-Disposition: inline\r\n'
			.'\r\n'
			.'Test Mail\r\n'
			.'\r\n'
			.'\r\n'
			.'-{6}\w+\r\n'
			.'Content-Type: text/html;\r\n\t'
			.'charset="ascii"\r\n'
			.'Content-Transfer-Encoding: quoted-printable\r\n'
			.'Content-Disposition: inline\r\n'
			.'\r\n'
			.'<img src=3D"cid:\w+"/>\r\n'
			.'\r\n'
			.'\r\n'
			.'-{6}\w+-{2}\r\n'
			.'\r\n'
			.'-{6}\w+\r\n'
			.'Content-Type: application/octet-stream;\r\n\t'
			.'name="test.jpg"\r\n'
			.'Content-Transfer-Encoding: base64\r\n'
			.'Content-Description: test.jpg\r\n'
			.'Content-ID: <\w+>\r\n'
			.'Content-Disposition: inline;\r\n\t'
			.'filename="test.jpg"\r\n'
			.'\r\n'
			.'YWJj\r\n'
			.'\r\n'
			.'\r\n'
			.'-{6}\w+-{2}'
			.'$@'
			, $mail->composeMail()
		);

		$mail = new EMail('test@example.com', 'Test Mail');
		$this->assertInstanceOf(EMail::class, $mail);

		$testFile = tempnam(sys_get_temp_dir(), 'EMailTest_');
		file_put_contents($testFile, basename(__FILE__));
		$mail->addRecipient('test2@example.com');
		$mail->setTextBody('Test Mail');
		$mail->addAttachment($testFile);
		$this->assertRegExp(
			'@^'
			.'From: <test\@example\.com>\r\n'
			.'To: <test2\@example\.com>\r\n'
			.'Subject: Test Mail\r\n'
			.'User-Agent: PHP/unrealization/eMail\r\n'
			.'Date: \w{3}, \d{1,2} \w{3} \d{4} \d{2}:\d{2}:\d{2} [+-]\d{4}\r\n'
			.'Content-Type: multipart/mixed;\r\n\t'
			.'boundary="-{4}\w+"\r\n'
			.'MIME-Version: 1\.0\r\n'
			.'\r\n'
			.'-{6}\w+\r\n'
			.'Content-Type: text/plain;\r\n\t'
			.'charset="ascii"\r\n'
			.'Content-Transfer-Encoding: quoted-printable\r\n'
			.'Content-Disposition: inline\r\n'
			.'\r\n'
			.'Test Mail\r\n'
			.'\r\n'
			.'\r\n'
			.'\r\n'
			.'-{6}\w+\r\n'
			.'Content-Type: application/octet-stream;\r\n\t'
			.'name="'.basename($testFile).'"\r\n'
			.'Content-Transfer-Encoding: base64\r\n'
			.'Content-Description: '.basename($testFile).'\r\n'
			.'Content-ID: <\w+>\r\n'
			.'Content-Disposition: attachment;\r\n\t'
			.'filename="'.basename($testFile).'"\r\n'
			.'\r\n'
			.'RU1haWxUZXN0LnBocA==\r\n'
			.'\r\n'
			.'\r\n'
			.'-{6}\w+-{2}'
			.'$@'
			, $mail->composeMail()
		);
		unlink($testFile);

		$mail = new EMail('test@example.com', 'Test Mail');
		$this->assertInstanceOf(EMail::class, $mail);
		
		$mail->addRecipient('test2@example.com');
		$mail->setTextBody('Test Mail');
		$mail->setEncoding('8bit');
		$mail->addHeader('X-Test-Header: testValue');
		$this->assertRegExp(
			'@^'
			.'From: <test\@example\.com>\r\n'
			.'To: <test2\@example\.com>\r\n'
			.'Subject: Test Mail\r\n'
			.'User-Agent: PHP/unrealization/eMail\r\n'
			.'Date: \w{3}, \d{1,2} \w{3} \d{4} \d{2}:\d{2}:\d{2} [+-]\d{4}\r\n'
			.'MIME-Version: 1\.0\r\n'
			.'X-Test-Header: testValue\r\n'
			.'Content-Type: text/plain;\r\n\t'
			.'charset="ascii"\r\n'
			.'Content-Transfer-Encoding: 8bit\r\n'
			.'Content-Disposition: inline\r\n'
			.'\r\n'
			.'Test Mail\r\n'
			.'\r\n'
			.'$@'
			, $mail->composeMail()
		);

		$mail->setEncoding('quoted-printable');
		$this->assertRegExp(
			'@^'
			.'From: <test\@example\.com>\r\n'
			.'To: <test2\@example\.com>\r\n'
			.'Subject: Test Mail\r\n'
			.'User-Agent: PHP/unrealization/eMail\r\n'
			.'Date: \w{3}, \d{1,2} \w{3} \d{4} \d{2}:\d{2}:\d{2} [+-]\d{4}\r\n'
			.'MIME-Version: 1\.0\r\n'
			.'X-Test-Header: testValue\r\n'
			.'Content-Type: text/plain;\r\n\t'
			.'charset="ascii"\r\n'
			.'Content-Transfer-Encoding: quoted-printable\r\n'
			.'Content-Disposition: inline\r\n'
			.'\r\n'
			.'Test Mail\r\n'
			.'\r\n'
			.'$@'
			, $mail->composeMail()
		);

		$mail->setEncoding('base64');
		$this->assertRegExp(
			'@^'
			.'From: <test\@example\.com>\r\n'
			.'To: <test2\@example\.com>\r\n'
			.'Subject: Test Mail\r\n'
			.'User-Agent: PHP/unrealization/eMail\r\n'
			.'Date: \w{3}, \d{1,2} \w{3} \d{4} \d{2}:\d{2}:\d{2} [+-]\d{4}\r\n'
			.'MIME-Version: 1\.0\r\n'
			.'X-Test-Header: testValue\r\n'
			.'Content-Type: text/plain;\r\n\t'
			.'charset="ascii"\r\n'
			.'Content-Transfer-Encoding: base64\r\n'
			.'Content-Disposition: inline\r\n'
			.'\r\n'
			.'VGVzdCBNYWls\r\n'
			.'\r\n'
			.'\r\n'
			.'$@'
			, $mail->composeMail()
		);
	}

	public function testUnknownEncoding()
	{
		$mail = new EMail('test@example.com', 'Test Mail');
		$this->assertInstanceOf(EMail::class, $mail);

		$this->expectException(\InvalidArgumentException::class);
		$mail->setEncoding('Unknown Encoding');
	}

	public function testUnknownRecipientType()
	{
		$mail = new EMail('test@example.com', 'Test Mail');
		$this->assertInstanceOf(EMail::class, $mail);

		$this->expectException(\InvalidArgumentException::class);
		$mail->addRecipient('test2@example.com', 'Unknown Recipient Type');
	}

	public function testInvalidMail1()
	{
		$mail = new EMail('test@example.com', 'Test Mail');
		$this->assertInstanceOf(EMail::class, $mail);

		$this->expectException(\Exception::class);
		$mail->addRecipient('<<test2@example.com>>');
	}

	public function testInvalidMail2()
	{
		$mail = new EMail('test@example.com', 'Test Mail');
		$this->assertInstanceOf(EMail::class, $mail);

		$this->expectException(\Exception::class);
		$mail->addRecipient('test2@@example.com');
	}

	public function testMissingAttachment()
	{
		$mail = new EMail('test@example.com', 'Test Mail');
		$this->assertInstanceOf(EMail::class, $mail);

		$mail->addRecipient('test2@example.com');
		$mail->setTextBody('Test Mail');
		$mail->addAttachment(__DIR__.'/'.uniqid().'.'.uniqid());

		$this->expectException(\Exception::class);
		$mail->composeMail();
	}
}
