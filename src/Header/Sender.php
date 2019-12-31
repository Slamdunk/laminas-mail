<?php

/**
 * @see       https://github.com/laminas/laminas-mail for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mail/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mail/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mail\Header;

use Laminas\Mail;

class Sender implements HeaderInterface
{
    /**
     * @var \Laminas\Mail\Address\AddressInterface
     */
    protected $address;

    /**
     * Header encoding
     *
     * @var string
     */
    protected $encoding = 'ASCII';

    public static function fromString($headerLine)
    {
        $decodedLine = iconv_mime_decode($headerLine, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
        list($name, $value) = GenericHeader::splitHeaderLine($decodedLine);

        // check to ensure proper header type for this factory
        if (strtolower($name) !== 'sender') {
            throw new Exception\InvalidArgumentException('Invalid header line for Sender string');
        }

        $header = new static();
        if ($decodedLine != $headerLine) {
            $header->setEncoding('UTF-8');
        }

        // Check for address, and set if found
        if (preg_match('/^(?P<name>.*?)<(?P<email>[^>]+)>$/', $value, $matches)) {
            $name = $matches['name'];
            if (empty($name)) {
                $name = null;
            } else {
                $name = iconv_mime_decode($name, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            }
            $header->setAddress($matches['email'], $name);
        }

        return $header;
    }

    public function getFieldName()
    {
        return 'Sender';
    }

    public function getFieldValue($format = HeaderInterface::FORMAT_RAW)
    {
        if (!$this->address instanceof Mail\Address\AddressInterface) {
            return '';
        }

        $email = sprintf('<%s>', $this->address->getEmail());
        $name  = $this->address->getName();
        if (!empty($name)) {
            $encoding = $this->getEncoding();
            if ($format == HeaderInterface::FORMAT_ENCODED
                && 'ASCII' !== $encoding
            ) {
                $name  = HeaderWrap::mimeEncodeValue($name, $encoding);
            }
            $email = sprintf('%s %s', $name, $email);
        }
        return $email;
    }

    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
        return $this;
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public function toString()
    {
        return 'Sender: ' . $this->getFieldValue(HeaderInterface::FORMAT_ENCODED);
    }

    /**
     * Set the address used in this header
     *
     * @param  string|\Laminas\Mail\Address\AddressInterface $emailOrAddress
     * @param  null|string $name
     * @throws Exception\InvalidArgumentException
     * @return Sender
     */
    public function setAddress($emailOrAddress, $name = null)
    {
        if (is_string($emailOrAddress)) {
            $emailOrAddress = new Mail\Address($emailOrAddress, $name);
        } elseif (!$emailOrAddress instanceof Mail\Address\AddressInterface) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a string or AddressInterface object; received "%s"',
                __METHOD__,
                (is_object($emailOrAddress) ? get_class($emailOrAddress) : gettype($emailOrAddress))
            ));
        }
        $this->address = $emailOrAddress;
        return $this;
    }

    /**
     * Retrieve the internal address from this header
     *
     * @return \Laminas\Mail\Address\AddressInterface|null
     */
    public function getAddress()
    {
        return $this->address;
    }
}
