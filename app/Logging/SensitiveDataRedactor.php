<?php

namespace App\Logging;

use Monolog\LogRecord;

class SensitiveDataRedactor
{
    private const SENSITIVE_KEY_PATTERN = '/password|passwd|secret|token|api[_-]?key|authorization|cookie|private[_-]?key|client[_-]?secret|one[_-]?time[_-]?password|verification[_-]?code|reset[_-]?code|\botp\b/i';

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: $this->redactMessage($record->message),
            context: $this->redactArray($record->context),
            extra: $this->redactArray($record->extra),
        );
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private function redactArray(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_string($key) && preg_match(self::SENSITIVE_KEY_PATTERN, $key) === 1) {
                $values[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->redactArray($value);
            }
        }

        return $values;
    }

    private function redactMessage(string $message): string
    {
        $message = preg_replace(
            '/\b(authorization\s*[:=]\s*bearer\s+)[^\s,;]+/i',
            '$1[REDACTED]',
            $message,
        ) ?? $message;

        return preg_replace(
            '/\b(password|passwd|secret|token|api[_-]?key|authorization|cookie|private[_-]?key|client[_-]?secret|otp|verification[_-]?code|reset[_-]?code)\b(\s*[:=]\s*)(?:"[^"]*"|\'[^\']*\'|[^\s,;]+)/i',
            '$1$2[REDACTED]',
            $message,
        ) ?? $message;
    }
}
