<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/MY_Admin_Controller.php';

class Productos extends MY_Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('Playera_model');
        $this->load->library('upload');
    }


    public function index()
    {
        $data = array(
            'title' => 'Productos',
            'content' => 'productos'
        );

        $this->load->view('layouts/main', $data);
    }


    public function listar()
    {
        $productos = $this->Playera_model->listar_datatable();
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($productos));
    }

    public function guardar()
    {
        $id = $this->input->post('pla_id');

        if (!empty($id)) {
            $this->editar();
        } else {
            $this->crear();
        }
    }

    public function crear()
    {
        $datos = array(
            'pla_nombre' => $this->input->post('pla_nombre'),
            'pla_descripcion' => $this->input->post('pla_descripcion'),
            'pla_genero' => $this->input->post('pla_genero'),
            'pla_precio' => $this->input->post('pla_precio')
        );

        $imagen = NULL;

        if (!empty($_FILES['pla_imagen']['name'])) {
            $config['upload_path']   = './uploads/playeras/';
            $config['allowed_types'] = 'jpg|jpeg|png|webp';
            $config['max_size']      = 2048;
            $config['encrypt_name']  = TRUE;

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('pla_imagen')) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success' => false,
                        'error' => $this->upload->display_errors()
                    )));
                return;
            }

            $archivo = $this->upload->data();
            $imagen = $archivo['file_name'];
            $datos['pla_imagen'] = $imagen;
        }

        $stocks = array(
            'S' => $this->input->post('stock_S'),
            'M' => $this->input->post('stock_M'),
            'L' => $this->input->post('stock_L'),
            'XL' => $this->input->post('stock_XL'),
            '2XL' => $this->input->post('stock_2XL')
        );

        foreach ($stocks as $talla => $stock) {
            $stocks[$talla] = ($stock === NULL || $stock === '') ? 0 : (int) $stock;
        }

        $this->db->trans_start();

        $this->Playera_model->crear($datos);
        $pla_id = $this->db->insert_id();

        $this->Playera_model->guardar_tallas($pla_id, $stocks);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            if ($imagen !== NULL) {
                $ruta = './uploads/playeras/' . $imagen;
                if (file_exists($ruta)) {
                    unlink($ruta);
                }
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'No se pudo crear el producto.'
                )));
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => 'Producto creado correctamente.'
            )));
    }


    public function editar()
    {
        $id = $this->input->post('pla_id');
        $playera = $this->Playera_model->get_playera($id);

        if (!$playera) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'El producto no existe.'
                )));
            return;
        }

        $datos = array(
            'pla_nombre' => $this->input->post('pla_nombre'),
            'pla_descripcion' => $this->input->post('pla_descripcion'),
            'pla_genero' => $this->input->post('pla_genero'),
            'pla_precio' => $this->input->post('pla_precio')
        );

        $stocks = array(
            'S' => $this->input->post('stock_S'),
            'M' => $this->input->post('stock_M'),
            'L' => $this->input->post('stock_L'),
            'XL' => $this->input->post('stock_XL'),
            '2XL' => $this->input->post('stock_2XL')
        );

        foreach ($stocks as $talla => $stock) {
            $stocks[$talla] = ($stock === NULL || $stock === '') ? 0 : (int) $stock;
        }

        $imagen_nueva = NULL;

        if (!empty($_FILES['pla_imagen']['name'])) {
            $config['upload_path']   = './uploads/playeras/';
            $config['allowed_types'] = 'jpg|jpeg|png|webp';
            $config['max_size']      = 2048;
            $config['encrypt_name']  = TRUE;

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('pla_imagen')) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array(
                        'success' => false,
                        'error' => $this->upload->display_errors()
                    )));
                return;
            }

            $archivo = $this->upload->data();
            $imagen_nueva = $archivo['file_name'];
            $datos['pla_imagen'] = $imagen_nueva;
        }

        $this->db->trans_start();

        $this->Playera_model->actualizar($id, $datos);
        $this->Playera_model->actualizar_tallas($id, $stocks);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            if ($imagen_nueva !== NULL) {
                $ruta = './uploads/playeras/' . $imagen_nueva;
                if (file_exists($ruta)) {
                    unlink($ruta);
                }
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'No se pudo actualizar el producto.'
                )));
            return;
        }

        if ($imagen_nueva !== NULL) {
            if (!empty($playera->pla_imagen)) {
                $ruta_anterior = './uploads/playeras/' . $playera->pla_imagen;
                if (file_exists($ruta_anterior)) {
                    unlink($ruta_anterior);
                }
            }
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => 'Producto actualizado correctamente.'
            )));
    }
    
    public function eliminar()
    {
        $id = $this->input->post('pla_id');
        $playera = $this->Playera_model->get_playera($id);

        if (!$playera) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'El producto no existe.'
                )));
            return;
        }

        $resultado = $this->Playera_model->desactivar($id);

        if (!$resultado) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'success' => false,
                    'error' => 'No se pudo desactivar el producto.'
                )));
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => true,
                'message' => 'Producto desactivado correctamente.'
            )));
    }

    public function obtener($id = NULL)
    {
        if (empty($id)) {
            $id = $this->input->post('pla_id');
        }

        if (empty($id)) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'error' => 'ID nulo')));
            return;
        }

        $playera = $this->Playera_model->get_playera($id);

        if (!$playera) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'error' => 'No encontrada')));
            return;
        }

        $stocks = $this->Playera_model->obtener_stock($id);

        $json = json_encode(array(
            'success' => true,
            'producto' => $playera,
            'playera' => $playera,
            'stocks' => $stocks
        ));

        if ($json === false) {
            if (ob_get_length() > 0) { @ob_clean(); }
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('success' => false, 'error' => json_last_error_msg())));
            return;
        }

        if (ob_get_length() > 0) { @ob_clean(); }
        $this->output
            ->set_content_type('application/json')
            ->set_output($json);
    }

    public function exportar_excel()
    {
        $ids_str = $this->input->get('ids');
        $ids = array();
        if (!empty($ids_str)) {
            $ids = explode(',', $ids_str);
        }

        $productos = $this->Playera_model->listar_por_ids($ids);

        require_once APPPATH . 'libraries/PHPExcel/Classes/PHPExcel.php';

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();

        $objPHPExcel->getProperties()
            ->setCreator("Sistema")
            ->setTitle("Productos Exportados")
            ->setSubject("Productos Exportados");

        $sheet->setTitle("Productos");

        // Encabezados pedidos por el usuario: Nombre, Descripción, Género, Precio, S, M, L, XL, 2XL
        $sheet->setCellValue('A1', 'Nombre');
        $sheet->setCellValue('B1', 'Descripción');
        $sheet->setCellValue('C1', 'Género');
        $sheet->setCellValue('D1', 'Precio');
        $sheet->setCellValue('E1', 'S');
        $sheet->setCellValue('F1', 'M');
        $sheet->setCellValue('G1', 'L');
        $sheet->setCellValue('H1', 'XL');
        $sheet->setCellValue('I1', '2XL');

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

        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($productos as $producto) {
            $genero = ($producto->pla_genero === 'H' || strtolower($producto->pla_genero) === 'hombre') ? 'hombre' : 'mujer';

            $sheet->setCellValue('A' . $row, $producto->pla_nombre);
            $sheet->setCellValue('B' . $row, $producto->pla_descripcion);
            $sheet->setCellValue('C' . $row, $genero);
            $sheet->setCellValue('D' . $row, $producto->pla_precio);
            $sheet->setCellValue('E' . $row, isset($producto->stock_s) ? (int)$producto->stock_s : 0);
            $sheet->setCellValue('F' . $row, isset($producto->stock_m) ? (int)$producto->stock_m : 0);
            $sheet->setCellValue('G' . $row, isset($producto->stock_l) ? (int)$producto->stock_l : 0);
            $sheet->setCellValue('H' . $row, isset($producto->stock_xl) ? (int)$producto->stock_xl : 0);
            $sheet->setCellValue('I' . $row, isset($producto->stock_2xl) ? (int)$producto->stock_2xl : 0);

            $dataStyle = array(
                'font' => array('size' => 10, 'name' => 'Calibri'),
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => array('rgb' => 'E5E7EB')
                    )
                )
            );
            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($dataStyle);
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        if (ob_get_length() > 0) { @ob_clean(); }

        $filename = 'productos_exportados_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    public function exportar_pdf()
    {
        $ids_str = $this->input->get('ids');
        $ids = array();
        if (!empty($ids_str)) {
            $ids = explode(',', $ids_str);
        }

        $productos = $this->Playera_model->listar_por_ids($ids);

        require_once APPPATH . 'libraries/tcpdf/tcpdf.php';

        $pdf = new tcpdf();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Productos Registrados');

        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 9);

        $html = '<h1 style="color:#4A2D5F; text-align:center;">Catálogo de Productos</h1>';
        $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%; border-collapse:collapse;">';
        $html .= '<tr style="background-color:#4A2D5F; color:#ffffff; font-weight:bold;">';
        $html .= '<th>Nombre</th><th>Descripción</th><th>Género</th><th>Precio</th><th>S</th><th>M</th><th>L</th><th>XL</th><th>2XL</th>';
        $html .= '</tr>';

        foreach ($productos as $producto) {
            $genero = ($producto->pla_genero === 'H' || strtolower($producto->pla_genero) === 'hombre') ? 'hombre' : 'mujer';

            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($producto->pla_nombre) . '</td>';
            $html .= '<td>' . htmlspecialchars($producto->pla_descripcion) . '</td>';
            $html .= '<td>' . htmlspecialchars($genero) . '</td>';
            $html .= '<td>$' . number_format($producto->pla_precio, 2) . '</td>';
            $html .= '<td>' . (isset($producto->stock_s) ? (int)$producto->stock_s : 0) . '</td>';
            $html .= '<td>' . (isset($producto->stock_m) ? (int)$producto->stock_m : 0) . '</td>';
            $html .= '<td>' . (isset($producto->stock_l) ? (int)$producto->stock_l : 0) . '</td>';
            $html .= '<td>' . (isset($producto->stock_xl) ? (int)$producto->stock_xl : 0) . '</td>';
            $html .= '<td>' . (isset($producto->stock_2xl) ? (int)$producto->stock_2xl : 0) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        if (ob_get_length() > 0) { @ob_clean(); }

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('productos_catálogo.pdf', 'D');
        exit;
    }

    public function plantilla_machote()
    {
        require_once APPPATH . 'libraries/PHPExcel/Classes/PHPExcel.php';

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle("Machote Playeras");

        // Encabezados: Nombre, Descripción, Género, Precio, S, M, L, XL, 2XL
        $sheet->setCellValue('A1', 'Nombre');
        $sheet->setCellValue('B1', 'Descripción');
        $sheet->setCellValue('C1', 'Género');
        $sheet->setCellValue('D1', 'Precio');
        $sheet->setCellValue('E1', 'Stock_S');
        $sheet->setCellValue('F1', 'Stock_M');
        $sheet->setCellValue('G1', 'Stock_L');
        $sheet->setCellValue('H1', 'Stock_XL');
        $sheet->setCellValue('I1', 'Stock_2XL');

        $headerStyle = array(
            'font' => array('bold' => true, 'color' => array('rgb' => 'FFFFFF'), 'size' => 11),
            'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => array('rgb' => '4A2D5F')),
            'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
        );
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        if (ob_get_length() > 0) { @ob_clean(); }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="machote_playeras.xlsx"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    public function importar_excel()
    {
        require_once APPPATH . 'libraries/PHPExcel/Classes/PHPExcel.php';

        $upload_path = FCPATH . 'uploads/';
        if (!is_dir($upload_path)) {
            @mkdir($upload_path, 0777, true);
        }

        $config['upload_path']   = $upload_path;
        $config['allowed_types'] = 'xlsx|xls';
        $config['max_size']      = 2048;

        $this->upload->initialize($config);

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
                $nombre      = trim((string)$sheet->getCell('A' . $row)->getValue());
                $descripcion = trim((string)$sheet->getCell('B' . $row)->getValue());
                $genero_raw  = strtoupper(trim((string)$sheet->getCell('C' . $row)->getValue()));
                $precio_raw  = $sheet->getCell('D' . $row)->getValue();
                $precio      = (float) str_replace(['$', ','], '', (string)$precio_raw);

                $stock_S   = (int) $sheet->getCell('E' . $row)->getValue();
                $stock_M   = (int) $sheet->getCell('F' . $row)->getValue();
                $stock_L   = (int) $sheet->getCell('G' . $row)->getValue();
                $stock_XL  = (int) $sheet->getCell('H' . $row)->getValue();
                $stock_2XL = (int) $sheet->getCell('I' . $row)->getValue();

                /*
                 * Validaciones
                 */
                if (empty($nombre)) {
                    $omitidos++;
                    continue;
                }

                /*
                 * Normalizar Género
                 */
                if ($genero_raw === 'HOMBRE' || $genero_raw === 'H') {
                    $genero = 'H';
                } else if ($genero_raw === 'MUJER' || $genero_raw === 'M') {
                    $genero = 'M';
                } else {
                    $genero = 'H';
                }

                $stocks = array(
                    'S'   => $stock_S < 0 ? 0 : $stock_S,
                    'M'   => $stock_M < 0 ? 0 : $stock_M,
                    'L'   => $stock_L < 0 ? 0 : $stock_L,
                    'XL'  => $stock_XL < 0 ? 0 : $stock_XL,
                    '2XL' => $stock_2XL < 0 ? 0 : $stock_2XL
                );

             
                $producto_existente = $this->db->get_where('playeras', array('pla_nombre' => $nombre))->row();

                if ($producto_existente) {
                    $pla_id = $producto_existente->pla_id;

                    $this->Playera_model->actualizar($pla_id, array(
                        'pla_nombre'      => $nombre,
                        'pla_descripcion' => $descripcion,
                        'pla_genero'      => $genero,
                        'pla_precio'      => $precio
                    ));

                    $this->Playera_model->actualizar_tallas($pla_id, $stocks);

                } else {
                    $datos = array(
                        'pla_nombre'      => $nombre,
                        'pla_descripcion' => $descripcion,
                        'pla_genero'      => $genero,
                        'pla_precio'      => $precio
                    );

                    $this->db->trans_start();

                    $this->Playera_model->crear($datos);
                    $pla_id = $this->db->insert_id();

                    $this->Playera_model->guardar_tallas($pla_id, $stocks);

                    $this->db->trans_complete();
                }

                $importados++;
            }

            /*
             * Eliminar archivo temporal
             */
            if (file_exists($ruta_archivo)) {
                unlink($ruta_archivo);
            }

            if (ob_get_length() > 0) { @ob_clean(); }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'message' => "Importación completada: $importados productos procesados ($omitidos omitidos)."
                ]));

        } catch (Exception $e) {
            if (file_exists($ruta_archivo)) {
                unlink($ruta_archivo);
            }

            if (ob_get_length() > 0) { @ob_clean(); }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Error al procesar el archivo Excel: ' . $e->getMessage()
                ]));
        }
    }

}