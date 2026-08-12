<?php

declare(strict_types=1);

/**
 * Lightweight PDF text + MCQ extraction for simple (text-based) PDF files.
 * Works for PDFs whose text is stored in uncompressed or FlateDecode streams.
 */

if (!function_exists('pdf_unescape')) {
    function pdf_unescape(string $s): string
    {
        $out = '';
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $ch = $s[$i];
            if ($ch === '\\' && $i + 1 < $len) {
                $next = $s[++$i];
                switch ($next) {
                    case 'n': $out .= "\n"; break;
                    case 'r': $out .= "\r"; break;
                    case 't': $out .= "\t"; break;
                    case '(': $out .= '('; break;
                    case ')': $out .= ')'; break;
                    case '\\': $out .= '\\'; break;
                    default:
                        if (ctype_digit($next) && $i + 2 < $len) {
                            $oct = $next . $s[++$i] . $s[++$i];
                            $out .= chr((int) octdec($oct));
                        } else {
                            $out .= $next;
                        }
                }
            } else {
                $out .= $ch;
            }
        }
        return $out;
    }
}

if (!function_exists('pdf_content_text')) {
    /**
     * Extract plain text from one decoded PDF content stream.
     */
    function pdf_content_text(string $stream): string
    {
        $out = '';

        if (preg_match_all('/\[(?:\([^\\\\()]*(?:\\\\.[^\\\\()]*)*\)|\d+(?:\.\d+)?\s*)+\]\s*TJ/', $stream, $matches)) {
            foreach ($matches[0] as $tj) {
                if (preg_match_all('/\([^\\\\()]*(?:\\\\.[^\\\\()]*)*\)/', $tj, $parts)) {
                    foreach ($parts[0] as $p) {
                        $out .= pdf_unescape(substr($p, 1, -1));
                    }
                }
                $out .= "\n";
            }
        }

        if (preg_match_all('/\([^\\\\()]*(?:\\\\.[^\\\\()]*)*\)\s*Tj/', $stream, $matches)) {
            foreach ($matches[0] as $tj) {
                $tj = preg_replace('/\s*Tj$/', '', $tj);
                $out .= pdf_unescape(substr($tj, 1, -1));
                $out .= "\n";
            }
        }

        return $out;
    }
}

if (!function_exists('pdf_extract_text')) {
    /**
     * Read a PDF binary string and return extracted plain text.
     */
    function pdf_extract_text(string $data): string
    {
        $all = '';

        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $data, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $content = $m[1];
                $decoded = false;
                if (function_exists('gzuncompress')) {
                    $decoded = @gzuncompress($content);
                }
                if ($decoded === false && function_exists('gzinflate')) {
                    $decoded = @gzinflate($content);
                }
                if ($decoded === false) {
                    $decoded = $content;
                }
                $all .= pdf_content_text($decoded);
            }
        }

        return $all;
    }
}

if (!function_exists('parse_mcq_text')) {
    /**
     * Parse extracted text into MCQs.
     *
     * Supported shapes:
     *   1. Question text
     *   A) option one        (also a. A. (a) [b] etc.)
     *   B) option two
     *   C) option three
     *   D) option four
     *   Answer: B
     *
     * Returns array of ['text' => string, 'options' => ['A'..'D' => string], 'correct' => ?string]
     */
    function parse_mcq_text(string $text): array
    {
        $lines = preg_split('/\r?\n/', $text);
        $questions = [];
        $current = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^(?:Q(?:uestion)?[\.\s]*)?\s*(\d{1,3})[\.\)]\s+(.*)$/i', $line, $qm)) {
                if ($current !== null && $current['text'] !== '') {
                    $questions[] = $current;
                }
                $current = [
                    'text' => $qm[2],
                    'options' => [],
                    'correct' => null,
                ];
                continue;
            }

            if ($current === null) {
                continue;
            }

            if (preg_match('/^[\(\[{]?\s*([A-Da-d])\s*[\)\]}:.\.\-]\s*(.*)$/', $line, $om)) {
                $current['options'][strtoupper($om[1])] = $om[2];
                continue;
            }

            if (preg_match('/^(?:Answer|Ans|Correct)\s*[:=]?\s*[\(\[{]?\s*([A-Da-d])\s*[\)\]}]?/i', $line, $am)) {
                $current['correct'] = strtoupper($am[1]);
                continue;
            }

            if (!empty($current['options'])) {
                $current['text'] .= ' ' . $line;
            } else {
                $current['text'] .= ' ' . $line;
            }
        }

        if ($current !== null && $current['text'] !== '') {
            $questions[] = $current;
        }

        $result = [];
        foreach ($questions as $q) {
            $opts = $q['options'];
            if (count($opts) < 2) {
                continue;
            }
            $result[] = [
                'text' => $q['text'],
                'options' => $opts,
                'correct' => $q['correct'],
            ];
        }

        return $result;
    }
}
