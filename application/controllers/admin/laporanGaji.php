<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\ColumnDimension;
use PhpOffice\PhpSpreadsheet\Worksheet;

class LaporanGaji extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();

        if ($this->session->userdata('id_akses') != '1') {
            $this->session->set_flashdata('pesan', '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Anda belum Login!</strong> <button type="button" class="close" data-dismiss="alert" 
                        aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
            redirect('welcome');
        }
    }


    public function index()
    {
        $data['title'] = "Laporan Gaji Pegawai";
        if ((isset($_GET['bulan']) && $_GET['bulan'] != '') && (isset($_GET['tahun']) && $_GET['tahun'] != '')) {
            $bulan = $_GET['bulan'];
            $tahun = $_GET['tahun'];
            $bulantahun = $bulan . $tahun;
        } else {
            $bulan = date('m');
            $tahun = date('Y');
            $bulantahun = $bulan . $tahun;
        }
        $data['gaji'] = $this->db->query("SELECT data_pegawai.nip, 
            data_pegawai.nama_pegawai, data_pegawai.jenis_kelamin,
            data_jabatan.nama_jabatan, data_jabatan.gaji_pokok, 
            data_jabatan.transport, data_jabatan.uang_makan,
            data_kehadiran.alpha FROM data_pegawai
            INNER JOIN data_kehadiran ON data_kehadiran.nip=data_pegawai.nip
            INNER JOIN data_jabatan ON data_jabatan.id_jabatan=data_kehadiran.id_jabatan
            WHERE data_kehadiran.bulan='$bulantahun'
            ORDER BY data_pegawai.nama_pegawai ASC")->result();
        $data['potongan_cuti'] = $this->penggajianModel->potonganCutiBulanTahun($bulantahun);
        $this->load->view('templates_admin/header', $data);
        $this->load->view('templates_admin/sidebar');
        $this->load->view('admin/filterLaporanGaji');
        $this->load->view('templates_admin/footer');
    }

    public function cetakLaporanGaji()
    {
        $data['title'] = "Cetak Laporan Gaji Pegawai";

        if ((isset($_GET['bulan']) && $_GET['bulan'] != '') && (isset($_GET['tahun']) && $_GET['tahun'] != '')) {
            $bulan = $_GET['bulan'];
            $tahun = $_GET['tahun'];
            $bulantahun = $bulan . $tahun;
        } else {
            $bulan = date('m');
            $tahun = date('Y');
            $bulantahun = $bulan . $tahun;
        }
        $data['karyawan'] = $this->penggajianModel->listActiveKaryawan();
        $data['potongan'] = $this->penggajianModel->get_data('potongan_gaji')->result();
        $data['pph21'] = $this->penggajianModel->get_data('data_pph')->result();
        $data['cetakGaji'] = $this->db->query("SELECT data_pegawai.nip, 
                data_pegawai.nama_pegawai, data_pegawai.jenis_kelamin,
                data_jabatan.nama_jabatan, data_jabatan.gaji_pokok, 
                data_jabatan.transport, data_jabatan.uang_makan,
                data_kehadiran.alpha FROM data_pegawai
                INNER JOIN data_kehadiran ON data_kehadiran.nip=data_pegawai.nip
                INNER JOIN data_jabatan ON data_jabatan.id_jabatan=data_kehadiran.id_jabatan
                WHERE data_kehadiran.bulan='$bulantahun'
                ORDER BY data_pegawai.nama_pegawai ASC")->result();
        $data['potongan_cuti'] = $this->penggajianModel->potonganCutiBulanTahun($bulantahun);
        $this->load->view('templates_admin/header', $data);
        $this->load->view('admin/cetakDataGaji', $data);
    }
    public function export_excel()
    {
        if ((isset($_GET['bulan']) && $_GET['bulan'] != '') && (isset($_GET['tahun']) && $_GET['tahun'] != '')) {
            $bulan = $_GET['bulan'];
            $tahun = $_GET['tahun'];
            $bulantahun = $bulan . $tahun;
        } else {
            $bulan = date('m');
            $tahun = date('Y');
            $bulantahun = $bulan . $tahun;
        }
    
        $gaji = $this->db->query("SELECT data_pegawai.nip, 
            data_pegawai.nama_pegawai, data_pegawai.jenis_kelamin,
            data_jabatan.nama_jabatan, data_jabatan.gaji_pokok, 
            data_jabatan.transport, data_jabatan.uang_makan,
            data_kehadiran.alpha FROM data_pegawai
            INNER JOIN data_kehadiran ON data_kehadiran.nip=data_pegawai.nip
            INNER JOIN data_jabatan ON data_jabatan.id_jabatan=data_kehadiran.id_jabatan
            WHERE data_kehadiran.bulan='$bulantahun'
            ORDER BY data_pegawai.nama_pegawai ASC")->result();
    
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
    
        // properti dokumen
        $spreadsheet->getProperties()->setCreator("Admin")
                                     ->setLastModifiedBy("Admin")
                                     ->setTitle("Laporan Gaji");
    
        // Set the column headers
        $sheet->setCellValue('A1', 'NO')
              ->setCellValue('B1', 'NIP')
              ->setCellValue('C1', 'Nama Pegawai')
              ->setCellValue('D1', 'Jenis Kelamin')
              ->setCellValue('E1', 'Jabatan')
              ->setCellValue('F1', 'Gaji Pokok')
              ->setCellValue('G1', 'Tj Transport')
              ->setCellValue('H1', 'Uang Makan')
              ->setCellValue('I1', 'Potongan')
              ->setCellValue('J1', 'Total Gaji');
    
        // Add data to the spreadsheet
        $row = 2;
        $no = 1;
        foreach ($gaji as $data) {
            $sheet->setCellValue('A' . $row, $no++)
                  ->setCellValue('B' . $row, $data->nip)
                  ->setCellValue('C' . $row, $data->nama_pegawai)
                  ->setCellValue('D' . $row, $data->jenis_kelamin)
                  ->setCellValue('E' . $row, $data->nama_jabatan)
                  ->setCellValue('F' . $row, $data->gaji_pokok)
                  ->setCellValue('G' . $row, $data->transport)
                  ->setCellValue('H' . $row, $data->uang_makan)
                  ->setCellValue('I' . $row, $data->alpha) // alpha adalah potongan
                  ->setCellValue('J' . $row, ($data->gaji_pokok + $data->transport + $data->uang_makan - $data->alpha)); // Total Gaji
            $row++;
        }
    
        $sheet->setTitle("Laporan Gaji");
    
        // Set the header for download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Laporan Gaji ' . $bulantahun . '.xlsx"');
        header('Cache-Control: max-age=0');
    
        // Create Excel file
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    
        exit;
    }
}    