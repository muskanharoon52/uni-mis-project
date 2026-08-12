<?php
// result_publish_applications/includes/transcript_pdf.php
// Pure-PHP (no library) result transcript PDF built with the shared PDF primitives.

require_once dirname(__DIR__, 2) . '/examination/includes/sbe_datesheet_pdf.php';

if (!function_exists('result_transcript_pdf')) {
    /**
     * Build a valid A4 PDF result transcript.
     *
     * @param array $data Keys: student_name, roll_no, student_id, program_name, semester_name, batch_year,
     *                    exam_code, exam_title, exam_type, obtained_marks, total_marks, percentage,
     *                    grade, pass_fail_status, remarks, published_at, issued_by, university_name
     * @return string PDF binary
     */
    function result_transcript_pdf(array $data): string
    {
        $pageW = 595.28;
        $pageH = 841.89;
        $mL = 48;
        $mR = 48;
        $mT = 56;
        $mB = 46;
        $contentW = $pageW - $mL - $mR;

        $pages = [];
        $page = [];
        $y = 0;

        $newPage = function () use (&$pages, &$page, &$y, $pageH, $mT, $mL, $pageW, $mR, $data) {
            if (!empty($page)) {
                $pages[] = $page;
            }
            $page = [];
            $y = $pageH - $mT;

            $univ = (string) ($data['university_name'] ?? 'UNIVERSITY MIS');
            $page[] = "BT /F2 16 Tf {$mL} {$y} Td (" . _sbe_pdf_escape($univ) . ") Tj ET";
            $y -= 19;
            $page[] = "BT /F2 11 Tf {$mL} {$y} Td (Examination Result Transcript) Tj ET";
            $y -= 12;
            $page[] = "{$mL} {$y} m " . ($pageW - $mR) . " {$y} l S";
            $y -= 18;
        };

        $newPage();

        $labelX = $mL;
        $valueX = $mL + 170;

        $infoLine = function ($label, $value) use (&$page, &$y, $labelX, $valueX) {
            $page[] = "BT /F2 9.5 Tf {$labelX} {$y} Td (" . _sbe_pdf_escape($label) . ") Tj ET";
            $page[] = "BT /F1 9.5 Tf {$valueX} {$y} Td (" . _sbe_pdf_escape($value) . ") Tj ET";
            $y -= 15;
        };

        $page[] = "BT /F2 10 Tf {$mL} {$y} Td (Student Information) Tj ET";
        $y -= 15;

        $infoLine('Full Name', (string) ($data['student_name'] ?? ''));
        $infoLine('Roll Number', (string) ($data['roll_no'] ?? ''));
        $infoLine('Student ID', (string) ($data['student_id'] ?? ''));
        $infoLine('Program', (string) ($data['program_name'] ?? ''));
        $infoLine('Semester', (string) ($data['semester_name'] ?? ''));
        $infoLine('Batch Year', (string) ($data['batch_year'] ?? ''));

        $y -= 4;
        $page[] = "BT /F2 10 Tf {$mL} {$y} Td (Examination Details) Tj ET";
        $y -= 15;

        $infoLine('Exam Code', (string) ($data['exam_code'] ?? ''));
        $infoLine('Exam Title', _sbe_pdf_fit((string) ($data['exam_title'] ?? ''), $contentW - 170 - 6, 9.5));
        $infoLine('Exam Type', (string) ($data['exam_type'] ?? ''));

        $y -= 4;
        $page[] = "BT /F2 10 Tf {$mL} {$y} Td (Result) Tj ET";
        $y -= 15;

        $infoLine('Obtained Marks', number_format((float) ($data['obtained_marks'] ?? 0), 2));
        $infoLine('Total Marks', number_format((float) ($data['total_marks'] ?? 0), 2));
        $infoLine('Percentage', number_format((float) ($data['percentage'] ?? 0), 2) . '%');
        $infoLine('Grade', (string) ($data['grade'] ?? '-'));
        $infoLine('Result', (string) ($data['pass_fail_status'] ?? '-'));

        if (!empty($data['remarks'])) {
            $infoLine('Remarks', _sbe_pdf_fit((string) $data['remarks'], $contentW - 170 - 6, 9.5));
        }

        $y -= 10;
        $page[] = "{$mL} {$y} m " . ($pageW - $mR) . " {$y} l S";
        $y -= 18;

        $page[] = "BT /F1 8.5 Tf {$mL} {$y} Td (Published On: " . _sbe_pdf_escape((string) ($data['published_at'] ?? '-')) . ") Tj ET";
        $y -= 14;
        $page[] = "BT /F1 8.5 Tf {$mL} {$y} Td (Approved By: " . _sbe_pdf_escape((string) ($data['issued_by'] ?? '-')) . ") Tj ET";
        $y -= 40;
        $page[] = "{$mL} {$y} m " . ($mL + 140) . " {$y} l S";
        $y -= 12;
        $page[] = "BT /F1 8.5 Tf {$mL} {$y} Td (Controller of Examinations) Tj ET";

        if (!empty($page)) {
            $pages[] = $page;
        }

        $N = count($pages);
        $nCatalog = 1;
        $nPages = 2;
        $nF1 = 3 + 2 * $N;
        $nF2 = $nF1 + 1;

        $objects = [];
        $objects[$nCatalog] = "<< /Type /Catalog /Pages {$nPages} 0 R >>";

        $kids = [];
        for ($i = 0; $i < $N; $i++) { $kids[] = (string) (3 + $i) . ' 0 R'; }
        $objects[$nPages] = "<< /Type /Pages /Kids [ " . implode(' ', $kids) . " ] /Count {$N} >>";

        for ($i = 0; $i < $N; $i++) {
            $nPage = 3 + $i;
            $nCont = 3 + $N + $i;
            $objects[$nPage] = "<< /Type /Page /Parent {$nPages} 0 R /MediaBox [0 0 {$pageW} {$pageH}] "
                . "/Resources << /Font << /F1 {$nF1} 0 R /F2 {$nF2} 0 R >> >> /Contents {$nCont} 0 R >>";
        }

        for ($i = 0; $i < $N; $i++) {
            $nCont = 3 + $N + $i;
            $ops = $pages[$i];
            $ops[] = "BT /F1 7 Tf {$mL} " . ($mB - 8) . " Td (Page " . ($i + 1) . " of {$N}) Tj ET";
            $stream = implode("\n", $ops) . "\n";
            $objects[$nCont] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
        }

        $objects[$nF1] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[$nF2] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }
        $xrefPos = strlen($pdf);
        $count = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root {$nCatalog} 0 R >>\nstartxref\n{$xrefPos}\n%%EOF\n";

        return $pdf;
    }
}
