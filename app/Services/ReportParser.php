<?php

namespace App\Services;

class ReportParser
{
    public function parse(string $text): ?array
    {
        // Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Define regex patterns for each field
        // We use 'm' modifier for multiline matching
        $patterns = [
            'name' => '/(?:nama|pelapor)\s*[:=\-]\s*(.*)$/im',
            'title' => '/(?:judul|hal|perihal)\s*[:=\-]\s*(.*)$/im',
            'location' => '/(?:lokasi|alamat|tempat)\s*[:=\-]\s*(.*)$/im',
            'description' => '/(?:deskripsi|keterangan|isi|pengaduan)\s*[:=\-]\s*(.*)/is', // 's' modifier allows . to match newlines for the last field
        ];

        $data = [];
        $foundAny = false;

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $value = trim($matches[1]);

                // For description, we might grab too much if it's not the last field.
                // But usually Description is the long text at the end.
                // Let's rely on the user following the order or simple extraction for now.
                // To be safer, we could extract strictly line by line, but regex is more flexible for "Key: Value"

                // Cleanup: Stop description at the start of another known key if mixed order?
                // For now, assume Description is the main body or explicitly tagged.

                if (! empty($value)) {
                    $data[$key] = $value;
                    $foundAny = true;
                }
            }
        }

        // Validate required fields (at least Title or Description is needed to make sense)
        if (! isset($data['description']) && ! isset($data['title'])) {
            return null;
        }

        return $data;
    }
}
