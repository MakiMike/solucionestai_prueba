<?php 

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Admin_Controller.php';

class Usuarios extends MY_Admin_Controller {

    public function __construct()
    {
        parent::__construct();

        $this->load->model('Usuario_model');
    }

	public function index()
	{
        $data = array(
            'title' => 'Usuarios',
            'content' => 'usuarios'
        );

        $this->load->view('layouts/main', $data);
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
        $sheet = $objPHPExcel->getActiveSheet();

        // Establecer propiedades del documento
        $objPHPExcel->getProperties()->setCreator("Tu Nombre")
            ->setTitle("Usuarios Registrados")
            ->setSubject("Usuarios Registrados")
            ->setDescription("Exportación de usuarios registrados.")
            ->setKeywords("usuarios excel exportación")
            ->setCategory("Exportación");

        $sheet->setTitle('Usuarios');

        // Encabezados
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Nombre');
        $sheet->setCellValue('C1', 'Correo');
        $sheet->setCellValue('D1', 'Teléfono');
        $sheet->setCellValue('E1', 'Sexo');

        // Estilo de encabezados
        $headerStyle = array(
            'font' => array(
                'bold' => true,
                'color' => array('rgb' => 'FFFFFF'),
                'size' => 11,
                'name' => 'Calibri'
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'startcolor' => array('rgb' => '4A2D5F')
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => 'D1C4E9')
                )
            )
        );
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

        // Agregar datos de usuarios
        $row = 2;
        foreach ($usuarios as $usuario) {
            $sheet->setCellValue('A' . $row, $usuario->us_id);
            $sheet->setCellValue('B' . $row, $usuario->us_nombre);
            $sheet->setCellValue('C' . $row, $usuario->us_correo);
            $sheet->setCellValue('D' . $row, $usuario->us_telefono);
            $sheet->setCellValue('E' . $row, $usuario->us_sexo);

            $filaStyle = array(
                'font' => array(
                    'color' => array('rgb' => '1F1F1F'),
                    'size' => 10,
                    'name' => 'Calibri'
                ),
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => array('rgb' => 'E5E7EB')
                    )
                ),
                'fill' => array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'startcolor' => array('rgb' => ($row % 2 == 0) ? 'F7F3FA' : 'FFFFFF')
                )
            );

            $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($filaStyle);
            $row++;
        }

        // Ajuste de ancho de columnas
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(12);

        $sheet->getStyle('A1:E' . ($row - 1))->getAlignment()->setWrapText(true);
        $sheet->freezePane('A2');

        // Configurar encabezados para la descarga del archivo Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="usuarios.xlsx"');
        header('Cache-Control: max-age=0');

        // Crear el escritor y guardar el archivo Excel en la salida
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    public function exportar_pdf()
    {
        $ids = $this->input->get('ids');
        $ids = explode(',', $ids);
        $usuarios = $this->Usuario_model->listar_usuarios_por_ids($ids);

        // Cargar la librería de PDF
        require_once APPPATH . 'libraries/tcpdf/tcpdf.php';

        // Crear un nuevo objeto TCPDF
        $pdf = new tcpdf();

        // Establecer propiedades del documento
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Usuarios Registrados');
        $pdf->SetSubject('Usuarios Registrados');
        $pdf->SetKeywords('usuarios pdf exportación');

        // Agregar una página
        $pdf->AddPage();

        // Configurar la fuente del PDF
        $pdf->SetFont('helvetica', '', 10);

        // Construir el contenido HTML para el PDF
        //logo 
       // $html = '<img src="' . base_url('assets/img/logo.png') . '" alt="Logo" width="100">';
        $html = '<h1>Usuarios Registrados</h1>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Sexo</th></tr>';

        foreach ($usuarios as $usuario) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($usuario->us_id) . '</td>';
            $html .= '<td>' . htmlspecialchars($usuario->us_nombre) . '</td>';
            $html .= '<td>' . htmlspecialchars($usuario->us_correo) . '</td>';
            $html .= '<td>' . htmlspecialchars($usuario->us_telefono) . '</td>';
            $html .= '<td>' . htmlspecialchars($usuario->us_sexo) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        // Escribir el contenido HTML en el PDF
        $pdf->writeHTML($html, true, false, true, false, '');

        // Salida del PDF al navegador
        $pdf->Output('usuarios.pdf', 'D');
    }

    public function plantilla_machote()
    {
        // Cargar la librería de Excel
        require_once APPPATH . 'libraries/PHPExcel/Classes/PHPExcel.php';

        // Crear un nuevo objeto de Excel
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();

        // Establecer propiedades del documento
        $objPHPExcel->getProperties()->setCreator("Tu Nombre")
            ->setTitle("Plantilla")
            ->setSubject("Plantilla Machote")
            ->setDescription("Plantilla machote para usuarios.")
            ->setKeywords("plantilla machote excel")
            ->setCategory("Plantilla");

        // Agregar encabezados de columna
        $sheet->setCellValue('A1', 'Nombre');
        $sheet->setCellValue('B1', 'Correo');
        $sheet->setCellValue('C1', 'Teléfono');
        $sheet->setCellValue('D1', 'Contraseña (Hash)');
        $sheet->setCellValue('E1', 'Rol');
        $sheet->setCellValue('F1', 'Sexo');

        $headerStyle = array(
            'font' => array(
                'bold' => true,
                'color' => array('rgb' => 'FFFFFF'),
                'size' => 11,
                'name' => 'Calibri'
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'startcolor' => array('rgb' => '4A2D5F')
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ),
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => 'D1C4E9')
                )
            )
        );
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(12);

        $sheet->getStyle('A1:F100')->getAlignment()->setWrapText(true);
        $sheet->freezePane('A2');

        // Renombrar la hoja activa
        $sheet->setTitle('Plantilla Machote');

        // Establecer la hoja activa en la primera hoja
        $objPHPExcel->setActiveSheetIndex(0);

        // Configurar encabezados para la descarga del archivo Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="plantilla_machote.xlsx"');
        header('Cache-Control: max-age=0');

        // Crear el escritor y guardar el archivo Excel en la salida
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    public function importar_excel()
{
    require_once APPPATH . 'libraries/PHPExcel/Classes/PHPExcel.php';

    $config['upload_path'] = './uploads/';
    $config['allowed_types'] = 'xlsx|xls';
    $config['max_size'] = 2048;

    $this->load->library('upload', $config);

    if (!$this->upload->do_upload('excelFile')) {

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => false,
                'message' => strip_tags($this->upload->display_errors())
            ]));

        return;
    }

    $data = $this->upload->data();
    $ruta_archivo = $data['full_path'];

    try {

        $objPHPExcel = PHPExcel_IOFactory::load($ruta_archivo);
        $sheet = $objPHPExcel->getActiveSheet();

        $highestRow = $sheet->getHighestRow();

        $importados = 0;
        $omitidos = 0;

        for ($row = 2; $row <= $highestRow; $row++) {

            $nombre = trim($sheet->getCell('A' . $row)->getValue());
            $correo = trim($sheet->getCell('B' . $row)->getValue());
            $telefono = trim($sheet->getCell('C' . $row)->getValue());
            $contrasenia = $sheet->getCell('D' . $row)->getValue();
            $rol = strtoupper(trim($sheet->getCell('E' . $row)->getValue()));
            $sexo = strtoupper(trim($sheet->getCell('F' . $row)->getValue()));

           //VALIDACIONES
            if (empty($nombre) || empty($correo) || empty($contrasenia)) {
                $omitidos++;
                continue;
            }

            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $omitidos++;
                continue;
            }

        
            if (empty($telefono)) {
                $telefono = NULL;

            } else {
                 if (!preg_match('/^\+?[0-9]{7,15}$/', $telefono)) {
                    $omitidos++;
                    continue;
                }

                if (strlen($telefono) > 10) {
                    $omitidos++;
                    continue;
                }

                $telefono_existente =
                    $this->Usuario_model->obtener_por_telefono($telefono);

                if ($telefono_existente) {
                    $omitidos++;
                    continue;
                }
            }


            if (empty($sexo)) {
                $omitidos++;
                continue;
            }

            if (!in_array($sexo, ['H', 'M', 'O'])) {
                $sexo = 'O';
            }


            if (!in_array($rol, ['A', 'U'])) {
                $rol = 'U';
            }

            $usuario_existente =
                $this->Usuario_model->obtener_por_correo($correo);

            if ($usuario_existente) {
                $omitidos++;
                continue;
            }

            $contrasenia_hash =
                password_hash($contrasenia, PASSWORD_DEFAULT);

            
            //GUARDAR
            $this->Usuario_model->guardar_usuario(
                $nombre,
                $correo,
                $telefono,
                $contrasenia_hash,
                $rol,
                $sexo
            );

            $importados++;
        }

        //ELIMINAR EL ARCHIVO SUBIDO DESPUÉS DE PROCESARLO
        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }

        //JSON
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'message' =>
                    'Importación completada. ' .
                    'Usuarios importados: ' . $importados .
                    '. Filas omitidas: ' . $omitidos . '.'
            ]));

    } catch (Exception $e) {

        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => false,
                'message' => 'Error al procesar el archivo Excel: ' . $e->getMessage()
            ]));
    }
}

}