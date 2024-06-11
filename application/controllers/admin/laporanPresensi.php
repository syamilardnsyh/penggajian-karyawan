<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanPresensi extends CI_Controller
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
        $data['title'] = "Laporan Presensi";
        if ((isset($_GET['bulan']) && $_GET['bulan'] != '') && (isset($_GET['tahun']) && $_GET['tahun'] != '')) {
            $bulan = $_GET['bulan'];
            $tahun = $_GET['tahun'];
            $bulantahun = $bulan . $tahun;
        } else {
            $bulan = date('m');
            $tahun = date('Y');
            $bulantahun = $bulan . $tahun;
        }
        $data['presensi'] = $this->db->query("SELECT * FROM data_kehadiran
            WHERE bulan='$bulantahun'
            ORDER BY nip ASC")->result();
        $this->load->view('templates_admin/header', $data);
        $this->load->view('templates_admin/sidebar');
        $this->load->view('admin/filterLaporanPresensi');
        $this->load->view('templates_admin/footer');
    }

    public function cetakLaporanPresensi()
    {
        $data['title'] = "Cetak Laporan Presensi Pegawai";
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
        $data['lap_kehadiran'] = $this->db->query("SELECT * FROM data_kehadiran
            WHERE bulan='$bulantahun'
            ORDER BY nip ASC")->result();
        $this->load->view('templates_admin/header', $data);
        $this->load->view('admin/cetakLaporanPresensi');
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

        $presensi = $this->db->query("SELECT data_kehadiran.*, data_pegawai.nama_pegawai FROM data_kehadiran
            JOIN data_pegawai ON data_kehadiran.nip = data_pegawai.nip
            WHERE bulan='$bulantahun' AND data_pegawai.status_keaktifan='aktif'
            ORDER BY data_kehadiran.nip ASC")->result();

        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator("Admin")
                                     ->setLastModifiedBy("Admin")
                                     ->setTitle("Laporan Presensi");

        // Set the column headers
        $sheet->setCellValue('A1', 'NO')
              ->setCellValue('B1', 'NIP')
              ->setCellValue('C1', 'Nama Pegawai')
              ->setCellValue('D1', 'Hadir')
              ->setCellValue('E1', 'Sakit')
              ->setCellValue('F1', 'Izin')
              ->setCellValue('G1', 'Alpha');

        // Add data to the spreadsheet
        $row = 2;
        $no = 1;
        foreach ($presensi as $data) {
            $sheet->setCellValue('A' . $row, $no++)
                  ->setCellValue('B' . $row, $data->nip)
                  ->setCellValue('C' . $row, $data->nama_pegawai)
                  ->setCellValue('D' . $row, $data->hadir)
                  ->setCellValue('E' . $row, $data->sakit)
                  ->setCellValue('F' . $row, $data->izin)
                  ->setCellValue('G' . $row, $data->alpha);
            $row++;
        }

        $sheet->setTitle("Laporan Presensi");

        // Set the header for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Laporan Presensi ' . $bulantahun . '.xlsx"');
        header('Cache-Control: max-age=0');

        // Create Excel file
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

        exit;
    }
}
