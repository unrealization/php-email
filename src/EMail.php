<?php
declare(strict_types=1);
/**
 * @package PHPClassCollection
 * @subpackage EMail
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 */
namespace unrealization\PHPClassCollection;
/**
 * @package PHPClassCollection
 * @subpackage EMail
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 * @version 2.0.1
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL 2.1
 */
class EMail
{
	/**
	 * The sender of the mail.
	 * @var string
	 */
	private $from;
	/**
	 * The list of recipients.
	 * @var array
	 */
	private $to = array();
	/**
	 * The list of cc-recipients.
	 * @var array
	 */
	private $cc = array();
	/**
	 * The list of bcc-recipients.
	 * @var array
	 */
	private $bcc = array();
	/**
	 * The subject of the mail.
	 * @var string
	 */
	private $subject;
	/**
	 * The clear-text body of the mail.
	 * @var string
	 */
	private $textBody;
	/**
	 * The HTML body of the mail.
	 * @var string
	 */
	private $htmlBody;
	/**
	 * The reply-to address.
	 * @var string
	 */
	private $replyTo;
	/**
	 * The notification-address.
	 * @var string
	 */
	private $notificationTo;
	/**
	 * The sending user-agent.
	 * @var string
	 */
	private $userAgent;
	/**
	 * The encoding of the content.
	 * @var string
	 */
	private $encoding = 'quoted-printable';
	/**
	 * The list of attached files.
	 * @var array
	 */
	private $attachedFiles = array();
	/**
	 * The list of additional headers.
	 * @var string
	 */
	private $extraHeaders = array();

	/**
	 * Constructor
	 * @param string $from
	 * @param string $subject
	 * @param string $replyTo
	 * @param string $notificationTo
	 * @param string $userAgent
	 */
	public function __construct(string $from, string $subject, string $replyTo = '', string $notificationTo = '', string $userAgent = 'PHP/unrealization/eMail')
	{
		if (preg_match('@<.*>@', $from) > 0)
		{
			$this->from = $from;
		}
		else
		{
			$this->from = '<'.$from.'>';
		}

		$this->subject = $subject;

		if ((preg_match('@<.*>@', $replyTo) > 0) || (empty($replyTo)))
		{
			$this->replyTo = $replyTo;
		}
		else
		{
			$this->replyTo = '<'.$replyTo.'>';
		}

		if ((preg_match('@<.*>@', $notificationTo) > 0) || (empty($notificationTo)))
		{
			$this->notificationTo = $notificationTo;
		}
		else
		{
			$this->notificationTo = '<'.$notificationTo.'>';
		}

		$this->userAgent = $userAgent;
	}

	/**
	 * Set the encoding of the email.
	 * @param string $encoding
	 * @throws \InvalidArgumentException
	 */
	public function setEncoding(string $encoding)
	{
		switch (strtoupper($encoding))
		{
			case '8BIT':
				$this->encoding = '8bit';
				break;
			case 'QUOTED-PRINTABLE':
				$this->encoding = 'quoted-printable';
				break;
			case 'BASE64':
				$this->encoding = 'base64';
				break;
			default:
				throw new \InvalidArgumentException('Unknown encoding');
		}
	}

	/**
	 * Set the clear-text body.
	 * @param string $body
	 */
	public function setTextBody(string $body)
	{
		$this->textBody = $body;
	}

	/**
	 * Set the HTML body.
	 * @param string $body
	 */
	public function setHtmlBody(string $body)
	{
		$this->htmlBody = $body;
	}

	/**
	 * Add a recipient.
	 * @param string $recipient
	 * @param string $type
	 * @throws \InvalidArgumentException
	 */
	public function addRecipient(string $recipient, string $type = 'To')
	{
		if (preg_match('@<.*>@', $recipient) == 0)
		{
			$recipient = '<'.$recipient.'>';
		}

		switch (strtoupper($type))
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
	}

	/**
	 * Add an attachment.
	 * @param string $fileName
	 * @param bool $inline
	 */
	public function addAttachment(string $fileName, bool $inline = false)
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
				'contentId'		=> md5(uniqid())
				);
	}

	/**
	 * Add an attachment by passing its content
	 * @param string $fileName
	 * @param string $data
	 * @param bool $inline
	 */
	public function addAttachmentData(string $fileName, string $data, bool $inline = false)
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
	}

	/**
	 * Add an additional header.
	 * @param string $header
	 */
	public function addHeader(string $header)
	{
		$this->extraHeaders[] = $header;
	}

	/**
	 * Parse the HTML body.
	 * @param string $htmlBody
	 * @return string
	 */
	private function parseHtml(string $htmlBody): string
	{
		$matches = array();
		preg_match_all('@<img.*src=(\'|")(.*)(\'|").*>@U', $htmlBody, $matches);

		for ($x = 0; $x < count($matches[2]); $x++)
		{
			for ($y = 0; $y < count($this->attachedFiles); $y++)
			{
				$fileName = explode('/', $this->attachedFiles[$y]['fileName']);
				$fileName = $fileName[count($fileName) - 1];

				if ($fileName == $matches[2][$x])
				{
					$htmlBody = preg_replace('@<img(.*)src=(\'|")'.$matches[2][$x].'(\'|")(.*)>@U', '<img$1src=$2cid:'.$this->attachedFiles[$y]['contentId'].'$3$4>', $htmlBody);
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
	private function encodeContent(string $content, string $overrideEncoding = null): string
	{
		if (!is_null($overrideEncoding))
		{
			$contentEncoding= $overrideEncoding;
		}
		else
		{
			$contentEncoding= $this->encoding;
		}

		switch ($contentEncoding)
		{
			case '8bit':
				return $content;
				break;
			case 'quoted-printable':
				return imap_8bit($content);
				break;
			case 'base64':
				return chunk_split(base64_encode($content));
				break;
			default:
				throw new \InvalidArgumentException('Unknown encoding');
		}
	}

	/**
	 * Compose the mail.
	 * @return string
	 * @throws \Exception
	 */
	public function composeMail(): string
	{
		$mail = 'From: '.$this->from."\r\n";

		if (!empty($this->replyTo))
		{
			$mail .= 'Reply-To: '.$this->replyTo."\r\n";
		}

		if (!empty($this->notificationTo))
		{
			$mail .= 'Disposition-Notification-To: '.$this->notificationTo."\r\n";
		}

		$mail .= 'To: '.implode(',', $this->to)."\r\n";
		$mail .= 'Subject: '.$this->subject."\r\n";
		$mail .= 'User-Agent: '.$this->userAgent."\r\n";
		$mail .= 'Date: '.date('r', time())."\r\n";

		if (count($this->attachedFiles) > 0)
		{
			$boundary='----'.md5(uniqid());
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

				if (!isset($this->attachedFiles[$x]['data']))
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
				$mail .= $this->encodeContent($this->attachedFiles[$x]['data'],'base64');
			}

			$mail .= "\r\n\r\n".'--'.$boundary.'--';
		}

		return $mail;
	}

	/**
	 * Send the mail.
	 * @return bool
	 */
	public function sendmail(): bool
	{
		$mail = $this->composeMail();
		$matches = array();
		preg_match('@^To: (.*)'."\r\n".'@Um', $mail, $matches);
		$to = $matches[1];
		preg_match('@^Subject: (.*)'."\r\n".'@Um', $mail, $matches);
		$subject = $matches[1];
		$mail = preg_replace('@^To:.*'."\r\n".'@Um', '', $mail);
		$mail = preg_replace('@^Subject:.*'."\r\n".'@Um', '', $mail);
		return mail($to, $subject, '', $mail);
	}
}
?>