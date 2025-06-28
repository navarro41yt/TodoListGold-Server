<?php

namespace Megacapa\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use TodoListGold\Security\EnvManager;
use TodoListGold\Utils\Dev\BaseClass;
use TodoListGold\Utils\Dev\ServerUtils;

enum MailPriority: int
{
    case HIGH = 1; # TODO (Antonio): For some reason, this is not working
    case NORMAL = 3;
    case LOW = 5;
    case UNSET = null;
}


enum AddressMode
{
    case TO;
    case CC;
    case BCC;
}


class Address
{
    public string $address;
    public AddressMode $mode;

    public function __construct(string $address, AddressMode $mode)
    {
        $this->address = $address;
        $this->mode = $mode;
    }
}


class Addresses
{
    public const MAIL_LEAD_DEV = 'anavarro41yt@gmail.com';
    public const MAIL_WEB_DEV = 'unknown@mail.com';


    /** @var Address[] */
    public array $addresses = [];

    public function __construct()
    {
        $this->addCC(self::MAIL_LEAD_DEV);
        $this->addCC(self::MAIL_WEB_DEV);
    }

    public static function constructDevTeam(): static
    {
        $addressess = new static();
        $addressess->addTo(self::MAIL_LEAD_DEV);
        $addressess->addTo(self::MAIL_WEB_DEV);

        return $addressess;
    }

    public function addAddress(Address $address): void
    {
        $this->addresses[] = $address;
    }

    public function addAddressWithMode(string $address, AddressMode $mode): void
    {
        $this->addresses[] = new Address($address, $mode);
    }

    public function addTo(string $address): void
    {
        $this->addAddressWithMode($address, AddressMode::TO);
    }

    public function addCC(string $address): void
    {
        $this->addAddressWithMode($address, AddressMode::CC);
    }

    public function addBCC(string $address): void
    {
        $this->addAddressWithMode($address, AddressMode::BCC);
    }

    public function clear(): void
    {
        $this->addresses = [];
    }
}


class Attachment
{
    public string $path;
    public string $name;

    public function __construct(string $path, string $name)
    {
        $this->path = $path;
        $this->name = $name;
    }

    public static function fromPath(string $path): static
    {
        return new static($path, basename($path));
    }
}


class Mailer extends BaseClass
{
    public const ASYNC_HELPER = ROOT_DIR . 'helpers/asyncMailer.php';

    public const SMTP_HOST = 'smtp.office365.com';
    public const SMTP_USERNAME = 'alertas@fuvex.com';
    public const SMTP_PORT = 587;

    private const PASSWORD_PATH = ROOT_DIR . DIRECTORY_SEPARATOR . 'password';

    /**
     * @param Attachment[] $attachments
     */
    public static function send(Addresses $addresses, string $subject, string $body, MailPriority $priority = MailPriority::NORMAL, array $attachments = []): void
    {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = self::SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = self::SMTP_USERNAME;
        $mail->Password = EnvManager::getStrFromPath(EnvManager::KEY_MAIL_CLIENT_DIR);
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = self::SMTP_PORT;

        $mail->setFrom(self::SMTP_USERNAME, 'Alertas Fuvex | Megacapa');

        foreach ($addresses->addresses as $address) {
            switch ($address->mode) {
                case AddressMode::TO:
                    $mail->addAddress($address->address);
                    break;
                case AddressMode::CC:
                    $mail->addCC($address->address);
                    break;
                case AddressMode::BCC:
                    $mail->addBCC($address->address);
                    break;
            }
        }

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->Priority = $priority->value;

        foreach ($attachments as $attachment) {
            $mail->addAttachment($attachment->path, $attachment->name);
        }

        if (!EnvManager::isProduction()) {
            $mail->clearAddresses();
            $mail->clearCCs();
            $mail->clearBCCs();
            $mail->addAddress(Addresses::MAIL_WEB_DEV);
            $mail->addCC(Addresses::MAIL_LEAD_DEV);
        }

        ServerUtils::asyncMailer($mail);
    }
}
