<?php 

defined('BASEPATH') OR exit('No direct script access allowed');

class Usuarios extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        if (!$this->session->userdata('logueado')) {

            redirect('login');
        }

        $this->load->model('Usuario_model');
    }

	public function index()
	{
        $this->load->view('usuarios');
    }
	
    public function listar()
    {
        $resultado = $this->Usuario_model->listar_datatable();

        echo json_encode($resultado);
    }


    public function exportar_excel()
    {
        $ids = $this->input->get('ids');
        $ids = explode(',', $ids);
        $usuarios = $this->Usuario_model->listar_usuarios_por_ids($ids);

        // Cargar la librería de Excel
        require_once APPPATH . 'libraries/PHPExcel/Classes/PHPExcel.php';

        // Crear un nuevo objeto de Excel
        $objPHPExcel = new PHPExcel();

        // Establecer propiedades del documento
        $objPHPExcel->getProperties()->setCreator("Tu Nombre")
            ->setTitle("Usuarios Registrados")
            ->setSubject("Usuarios Registrados")
            ->setDescription("Exportación de usuarios registrados.")
            ->setKeywords("usuarios excel exportación")
            ->setCategory("Exportación");

        // Agregar encabezados de columna
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'ID')
            ->setCellValue('B1', 'Nombre')
            ->setCellValue('C1', 'Correo')
            ->setCellValue('D1', 'Teléfono')
            ->setCellValue('E1', 'Sexo');

        // Agregar datos de usuarios
        $row = 2; // Comenzar desde la segunda fila
        foreach ($usuarios as $usuario) {
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $row, $usuario->us_id)
                ->setCellValue('B' . $row, $usuario->us_nombre)
                ->setCellValue('C' . $row, $usuario->us_correo)
                ->setCellValue('D' . $row, $usuario->us_telefono)
                ->setCellValue('E' . $row, $usuario->us_sexo);
            $row++;
        }

        // Renombrar la hoja activa
        $objPHPExcel->getActiveSheet()->setTitle('Usuarios');

        // Establecer la hoja activa en la primera hoja
        $objPHPExcel->setActiveSheetIndex(0);

        // Configurar encabezados para la descarga del archivo Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="usuarios.xlsx"');
        header('Cache-Control: max-age=0');

        // Crear el escritor y guardar el archivo Excel en la salida
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }
}