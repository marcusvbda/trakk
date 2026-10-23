<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A Telegram call that cannot go through. The message is safe to show: it never carries the bot token.
 */
final class TelegramException extends RuntimeException {}
