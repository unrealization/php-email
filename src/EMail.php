<?php
declare(strict_types=1);
/**
 * @package PHPClassCollection
 * @subpackage EMail
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 */
namespace unrealization;
/**
 * @package PHPClassCollection
 * @subpackage EMail
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 * @version 6.0.0
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL 2.1
 */
class EMail implements \Stringable
{
	/**
	 * The sender of the mail.
	 * @var string
	 */
	private string $from;
	/**
	 * The list of recipients.
	 * @var array
	 */
	private array $to = array();
	/**
	 * The list of cc-recipients.
	 * @var array
	 */
	private array $cc = array();
	/**
	 * The list of bcc-recipients.
	 * @var array
	 */
	private array $bcc = array();
	/**
	 * The subject of the mail.
	 * @var string
	 */
	private string $subject;
	/**
	 * The clear-text body of the mail.
	 * @var string
	 */
	private string $textBody;
	/**
	 * The HTML body of the mail.
	 * @var string
	 */
	private string $htmlBody;
	/**
	 * The reply-to address.
	 * @var string
	 */
	private ?string $replyTo = null;
	/**
	 * The notification-address.
	 * @var string
	 */
	private ?string $notificationTo = null;
	/**
	 * The sending user-agent.
	 * @var string
	 */
	private string $userAgent;
	/**
	 * The encoding of the content.
	 * @var string
	 */
	private string $encoding = 'quoted-printable';
	/**
	 * The list of attached files.
	 * @var array
	 */
	private array $attachedFiles = array();
	/**
	 * The list of additional headers.
	 * @var string
	 */
	private array $extraHeaders = array();

	/**
	 * Constructor
	 * @param string $from
	 * @param string $subject
	 * @param string $replyTo
	 * @param string $notificationTo
	 * @param string $userAgent
	 */
	public function __construct(string $from, string $subject, string $userAgent = 'PHP/unrealization/eMail')
	{
		$this->from = $this->validateAddress($from);
		$this->subject = $subject;
		$this->userAgent = $userAgent;
	}

	/**
	 * Output the email as a string.
	 * @return string
	 */
	public function __toString()
	{
		return $this->composeMail();
	}

	/**
	 * Set the encoding of the email.
	 * @param string $encoding
	 * @throws \InvalidArgumentException
	 * @return EMail
	 */
	public function setEncoding(string $encoding): EMail
	{
		switch (mb_strtoupper($encoding))
		{
			case '8BIT':
			case 'QUOTED-PRINTABLE':
			case 'BASE64':
				$this->encoding = mb_strtolower($encoding);
				break;
			default:
				throw new \InvalidArgumentException('Unknown encoding');
		}

		return $this;
	}

	/**
	 * Set the clear-text body.
	 * @param string $body
	 * @return EMail
	 */
	public function setTextBody(string $body): EMail
	{
		$this->textBody = $body;
		return $this;
	}

	/**
	 * Set the HTML body.
	 * @param string $body
	 * @return EMail
	 */
	public function setHtmlBody(string $body): EMail
	{
		$this->htmlBody = $body;
		return $this;
	}

	/**
	 * Add a recipient.
	 * @param string $recipient
	 * @param string $type
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 * @return EMail
	 */
	public function addRecipient(string $recipient, string $type = 'To'): EMail
	{
		$recipient = $this->validateAddress($recipient);

		switch (mb_strtoupper($type))
		{
			case 'TO':
				$this->to[] = $recipient;
				break;
			case 'CC':
				$this->cc[] = $recipient;
				break;
			case 'BCC':
				$this->bcc[] = $recipient;
				break;
			default:
				throw new \InvalidArgumentException('Unknown recipient type');
		}

		return $this;
	}

	/**
	 * Add an attachment.
	 * @param string $fileName
	 * @param bool $inline
	 * @return EMail
	 */
	public function addAttachment(string $fileName, bool $inline = false): EMail
	{
		return $this->addAttachmentData($fileName, null, $inline);
	}

	/**
	 * Add an attachment by passing its content
	 * @param string $fileName
	 * @param string $data
	 * @param bool $inline
	 * @return EMail
	 */
	public function addAttachmentData(string $fileName, ?string $data = null, bool $inline = false): EMail
	{
		if ($inline === true)
		{
			$disposition = 'inline';
		}
		else
		{
			$disposition = 'attachment';
		}

		$this->attachedFiles[] = array(
			'fileName'		=> $fileName,
			'disposition'	=> $disposition,
			'contentId'		=> md5(uniqid()),
			'data'			=> $data
		);
		return $this;
	}

	/**
	 * Add an additional header.
	 * @param string $header
	 * @return EMail
	 */
	public function addHeader(string $header): EMail
	{
		$this->extraHeaders[] = $header;
		return $this;
	}

	/**
	 * Set the reply-to address.
	 * @param string $replyTo
	 * @return EMail
	 */
	public function setReplyTo(?string $replyTo = null): EMail
	{
		if ((is_null($replyTo)) || (empty($replyTo)))
		{
			$this->replyTo = null;
			return $this;
		}

		$this->replyTo = $this->validateAddress($replyTo);
		return $this;
	}

	/**
	 * Set the notification-to address.
	 * @param string $notificationTo
	 * @return EMail
	 */
	public function setNotificationTo(?string $notificationTo = null): EMail
	{
		if ((is_null($notificationTo)) || (empty($notificationTo)))
		{
			$this->notificationTo = null;
			return $this;
		}

		$this->notificationTo = $this->validateAddress($notificationTo);
		return $this;
	}

	/**
	 * Parse the HTML body.
	 * @param string $htmlBody
	 * @return string
	 */
	private function parseHtml(string $htmlBody): string
	{
		$matches = MbRegEx::matchAll('<img.*src=(\'|")(.*)(\'|").*>', $htmlBody);

		if (empty($matches))
		{
			return $htmlBody;
		}

		foreach ($matches as $match)
		{
			foreach ($this->attachedFiles as $attachment)
			{
				$fileName = mb_split('/', $attachment['fileName']);
				$fileName = $fileName[count($fileName) - 1];

				if ($fileName === $match[2])
				{
					$htmlBody = preg_replace('@<img(.*)src=(\'|")'.$match[2].'(\'|")(.*)>@U', '<img$1src=$2cid:'.$attachment['contentId'].'$3$4>', $htmlBody);
				}
			}
		}

		return $htmlBody;
	}

	/**
	 * Encode the content.
	 * @param string $content
	 * @param string $overrideEncoding
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function encodeContent(string $content, ?string $overrideEncoding = null): string
	{
		if (!is_null($overrideEncoding))
		{
			$contentEncoding = $overrideEncoding;
		}
		else
		{
			$contentEncoding = $this->encoding;
		}

		switch ($contentEncoding)
		{
			case '8bit':
				return $content;
				break;
			case 'quoted-printable':
				return quoted_printable_encode($content);
				break;
			case 'base64':
				return chunk_split(base64_encode($content));
				break;
			default:
				throw new \InvalidArgumentException('Unknown encoding');
		}
	}

	/**
	 * Validate an email address.
	 * @param string $address
	 * @throws \Exception
	 * @return string
	 */
	private function validateAddress(string $address): string
	{
		$matches = array();

		if (preg_match('@(?|<)?([^<>]+)(?|>)?$@', $address, $matches) == 0)
		{
			throw new \Exception('Invalid email address');
		}

		$testAddress = filter_var($matches[1], FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE);

		if (is_null($testAddress))
		{
			throw new \Exception('Invalid email address');
		}

		if (preg_match('@<.*>@', $address) == 0)
		{
			$address = '<'.$address.'>';
		}

		return $address;
	}

	/**
	 * Compose the mail.
	 * @return string
	 * @throws \Exception
	 */
	public function composeMail(): string
	{
		$mail = 'From: '.$this->from."\r\n";

		if (!is_null($this->replyTo))
		{
			$mail .= 'Reply-To: '.$this->replyTo."\r\n";
		}

		if (!is_null($this->notificationTo))
		{
			$mail .= 'Disposition-Notification-To: '.$this->notificationTo."\r\n";
		}

		$mail .= 'To: '.implode(',', $this->to)."\r\n";
		$mail .= 'Subject: '.$this->subject."\r\n";
		$mail .= 'User-Agent: '.$this->userAgent."\r\n";
		$mail .= 'Date: '.date('r', time())."\r\n";

		if (count($this->attachedFiles) > 0)
		{
			$boundary = '----'.md5(uniqid());
			$mail .= 'Content-Type: multipart/mixed;'."\r\n\t".'boundary="'.$boundary.'"'."\r\n";
		}

		if (count($this->cc) > 0)
		{
			$mail .= 'Cc: '.implode(',', $this->cc)."\r\n";
		}

		if (count($this->bcc) > 0)
		{
			$mail .= 'Bcc: '.implode(',', $this->bcc)."\r\n";
		}

		$mail .= 'MIME-Version: 1.0'."\r\n";

		for ($x = 0; $x < count($this->extraHeaders); $x++)
		{
			$mail .= $this->extraHeaders[$x]."\r\n";
		}

		if (count($this->attachedFiles) > 0)
		{
			$mail .= "\r\n".'--'.$boundary."\r\n";
		}

		if ((!empty($this->textBody)) && (!empty($this->htmlBody)))
		{
			$messageBoundary = '----'.md5(uniqid());
			$mail .= 'Content-Type: multipart/alternative;'."\r\n\t".'boundary="'.$messageBoundary.'"'."\r\n";
			$mail .= "\r\n".'--'.$messageBoundary."\r\n";
		}

		if (!empty($this->textBody))
		{
			$mail .= 'Content-Type: text/plain;'."\r\n\t".'charset="'.strtolower(mb_detect_encoding($this->textBody)).'"'."\r\n";
			$mail .= 'Content-Transfer-Encoding: '.$this->encoding."\r\n";
			$mail .= 'Content-Disposition: inline'."\r\n\r\n";
			$mail .= $this->encodeContent($this->textBody)."\r\n\r\n";
		}

		if ((!empty($this->textBody)) && (!empty($this->htmlBody)))
		{
			$mail .= "\r\n".'--'.$messageBoundary."\r\n";
		}

		if (!empty($this->htmlBody))
		{
			$mail .= 'Content-Type: text/html;'."\r\n\t".'charset="'.strtolower(mb_detect_encoding($this->htmlBody)).'"'."\r\n";
			$mail .= 'Content-Transfer-Encoding: '.$this->encoding."\r\n";
			$mail .= 'Content-Disposition: inline'."\r\n\r\n";
			$mail .= $this->encodeContent($this->parseHtml($this->htmlBody))."\r\n\r\n";
		}

		if ((!empty($this->textBody)) && (!empty($this->htmlBody)))
		{
			$mail .= "\r\n".'--'.$messageBoundary.'--';
		}

		if (count($this->attachedFiles) > 0)
		{
			for ($x = 0; $x < count($this->attachedFiles); $x++)
			{
				$mail .= "\r\n\r\n".'--'.$boundary."\r\n";
				$fileName = explode('/', $this->attachedFiles[$x]['fileName']);
				$fileName = $fileName[count($fileName) - 1];

				if (is_null($this->attachedFiles[$x]['data']))
				{
					$file = @fopen($this->attachedFiles[$x]['fileName'],'r');

					if ($file == false)
					{
						throw new \Exception('Cannot open attachment file');
					}

					$this->attachedFiles[$x]['data'] = fread($file, filesize($this->attachedFiles[$x]['fileName']));
					fclose($file);
				}

				$mail .= 'Content-Type: application/octet-stream;'."\r\n\t".'name="'.$fileName.'"'."\r\n";
				$mail .= 'Content-Transfer-Encoding: base64'."\r\n";
				$mail .= 'Content-Description: '.$fileName."\r\n";
				$mail .= 'Content-ID: <'.$this->attachedFiles[$x]['contentId'].'>'."\r\n";
				$mail .= 'Content-Disposition: '.$this->attachedFiles[$x]['disposition'].';'."\r\n\t".'filename="'.$fileName.'"'."\r\n\r\n";
				$mail .= $this->encodeContent($this->attachedFiles[$x]['data'], 'base64');
			}

			$mail .= "\r\n\r\n".'--'.$boundary.'--';
		}

		return $mail;
	}

	/**
	 * Send the mail.
	 * @return bool
	 * @throws \Exception
	 */
	public function sendmail(): bool
	{
		$mail = $this->composeMail();
		$lines = explode("\r\n", $mail);
		$headers = array();
		$to = null;
		$subject = null;
		$matches = array();

		while (!empty($lines))
		{
			$line = $lines[0];
			array_splice($lines, 0, 1);

			if (empty($line))
			{
				break;
			}

			if (preg_match('@(To|Subject): (.*)$', $line, $matches))
			{
				switch ($matches[1])
				{
					case 'To':
						$to = $matches[2];
						continue 2;
						break;
					case 'Subject':
						$subject = $matches[2];
						continue 2;
						break;
					default:
						throw new \Exception('I do not know how to deal with '.$matches[1]);
						break;
				}
			}

			$headers[] = $line;
		}

		$headers = implode("\r\n", $headers);
		$content = implode("\r\n", $lines);

		if (is_null($to))
		{
			throw new \Exception('No recipient found.');
		}

		if (is_null($subject))
		{
			throw new \Exception('No subject found.');
		}

		return mail($to, $subject, $content, $headers);
	}
}
