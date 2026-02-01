<?php

namespace Tests\Unit;

use App\Services\ReportParser;
use PHPUnit\Framework\TestCase;

class ReportParserTest extends TestCase
{
    public function test_can_parse_valid_form()
    {
        $text = 'FORM PELAPORAN
        Nama: Budi Santoso
        Judul: Jalan Rusak
        Lokasi: Jl. Merdeka No. 10
        Deskripsi: Ada lubang besar di tengah jalan.';

        $parser = new ReportParser;
        $result = $parser->parse($text);

        $this->assertEquals('Budi Santoso', $result['name']);
        $this->assertEquals('Jalan Rusak', $result['title']);
        $this->assertEquals('Jl. Merdeka No. 10', $result['location']);
        $this->assertEquals('Ada lubang besar di tengah jalan.', $result['description']);
    }

    public function test_can_parse_messy_format()
    {
        $text = 'lapor dong min
        nama :   Siti Aminah  
        judul=Macet Parah
        lokasi- Pasar Baru
        keterangan: tolong ditertibkan PKL nya';

        $parser = new ReportParser;
        $result = $parser->parse($text);

        $this->assertEquals('Siti Aminah', $result['name']);
        $this->assertEquals('Macet Parah', $result['title']);
        $this->assertEquals('Pasar Baru', $result['location']);
        $this->assertEquals('tolong ditertibkan PKL nya', $result['description']);
    }

    public function test_returns_null_if_required_fields_missing()
    {
        $text = 'Halo min mau lapor';
        $parser = new ReportParser;
        $result = $parser->parse($text);

        $this->assertNull($result);
    }
}
