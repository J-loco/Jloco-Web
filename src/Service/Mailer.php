<?php

declare(strict_types=1);

namespace JLoco\Web\Service;

use LogicException;
use JLoco\Web\Config;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Sends emails rendered from templates/emails/<name>.txt.twig and .html.twig (MAILER_DSN, MAIL_FROM).
 */
final readonly class Mailer
{
    public function __construct(
        private Config $config,
        private Environment $twig,
    ) {
    }

    /** @param array<string, mixed> $context */
    public function send(string $to, string $subject, string $template, array $context): void
    {
        if (!$this->config->mailEnabled()) {
            throw new LogicException('Outgoing mail is not configured (MAILER_DSN, MAIL_FROM).');
        }
        $context += ['subject' => $subject];

        $email = new Email()
            ->from(new Address($this->config->mailFrom, $this->config->siteName))
            ->to($to)
            ->subject($subject)
            ->text($this->twig->render('emails/' . $template . '.txt.twig', $context))
            ->html($this->twig->render('emails/' . $template . '.html.twig', $context));

        new SymfonyMailer(Transport::fromDsn($this->config->mailerDsn))->send($email);
    }
}
