<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'core/MY_Admin_Controller.php';

class Ventas extends MY_Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Venta_model');
    }

    public function index()
    {
        $data = array(
            'title' => 'Ventas',
            'content' => 'ventas'
        );
        $this->load->view('layouts/main', $data);
    }

    public function listar()
    {
        $ventas = $this->Venta_model->listar_datatable();
        if (ob_get_length() > 0) { @ob_clean(); }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($ventas));
    }

    public function exportar_excel()
    {
        $ids_str = $this->input->get('ids');
        $ids = array();
        if (!empty($ids_str)) {
            $ids = explode(',', $ids_str);
        }

        $ventas = $this->Venta_model->obtener_ventas_por_ids($ids);

        require_once APPPATH . 'libraries/PHPExcel/Classes/PHPExcel.php';

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();

        $objPHPExcel->getProperties()
            ->setCreator("Sistema")
            ->setTitle("Reporte de Ventas")
            ->setSubject("Reporte de Ventas");

        $sheet->setTitle('Ventas');

        // Encabezados
        $sheet->setCellValue('A1', 'ID Pedido');
        $sheet->setCellValue('B1', 'Fecha');
        $sheet->setCellValue('C1', 'Cliente');
        $sheet->setCellValue('D1', 'Producto');
        $sheet->setCellValue('E1', 'Talla');
        $sheet->setCellValue('F1', 'Cantidad');
        $sheet->setCellValue('G1', 'Precio Unitario');
        $sheet->setCellValue('H1', 'Subtotal');
        $sheet->setCellValue('I1', 'Estado');

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
        $total_ventas = 0;

        foreach ($ventas as $venta) {
            $subtotal = (float) $venta->subtotal;
            $total_ventas += $subtotal;

            $sheet->setCellValue('A' . $row, $venta->ped_id);
            $sheet->setCellValue('B' . $row, $venta->ped_fecha);
            $sheet->setCellValue('C' . $row, $venta->us_nombre);
            $sheet->setCellValue('D' . $row, $venta->pla_nombre);
            $sheet->setCellValue('E' . $row, $venta->tal_nombre);
            $sheet->setCellValue('F' . $row, $venta->pde_cantidad);
            $sheet->setCellValue('G' . $row, '$' . number_format($venta->pde_precio, 2));
            $sheet->setCellValue('H' . $row, '$' . number_format($subtotal, 2));
            $sheet->setCellValue('I' . $row, ucfirst($venta->ped_estado));

            $filaStyle = array(
                'font' => array('size' => 10, 'name' => 'Calibri'),
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
            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($filaStyle);
            $row++;
        }

        // Fila Total General al final
        $sheet->setCellValue('A' . $row, 'TOTAL DE VENTAS');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->setCellValue('H' . $row, '$' . number_format($total_ventas, 2));

        $totalStyle = array(
            'font' => array(
                'bold' => true,
                'color' => array('rgb' => 'FFFFFF'),
                'size' => 11,
                'name' => 'Calibri'
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'startcolor' => array('rgb' => '2E7D32')
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            )
        );
        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($totalStyle);

        // Anchos de columna
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        if (ob_get_length() > 0) { @ob_clean(); }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="reporte_ventas.xlsx"');
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

        $ventas = $this->Venta_model->obtener_ventas_por_ids($ids);

        require_once APPPATH . 'libraries/tcpdf/tcpdf.php';

        $pdf = new tcpdf();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Reporte de Ventas');
        $pdf->SetSubject('Reporte de Ventas');

        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 9);

        $html = '<h1 style="color:#4A2D5F; text-align:center;">Reporte de Ventas</h1>';
        $html .= '<table border="1" cellpadding="4" cellspacing="0" style="width:100%; border-collapse:collapse;">';
        $html .= '<tr style="background-color:#4A2D5F; color:#ffffff; font-weight:bold;">';
        $html .= '<th>ID</th><th>Fecha</th><th>Cliente</th><th>Producto</th><th>Talla</th><th>Cant</th><th>Precio U.</th><th>Subtotal</th><th>Estado</th>';
        $html .= '</tr>';

        $total_ventas = 0;

        foreach ($ventas as $venta) {
            $subtotal = (float) $venta->subtotal;
            $total_ventas += $subtotal;

            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($venta->ped_id) . '</td>';
            $html .= '<td>' . htmlspecialchars(date('d/m/Y H:i', strtotime($venta->ped_fecha))) . '</td>';
            $html .= '<td>' . htmlspecialchars($venta->us_nombre) . '</td>';
            $html .= '<td>' . htmlspecialchars($venta->pla_nombre) . '</td>';
            $html .= '<td>' . htmlspecialchars($venta->tal_nombre) . '</td>';
            $html .= '<td>' . htmlspecialchars($venta->pde_cantidad) . '</td>';
            $html .= '<td>$' . number_format($venta->pde_precio, 2) . '</td>';
            $html .= '<td>$' . number_format($subtotal, 2) . '</td>';
            $html .= '<td>' . htmlspecialchars(ucfirst($venta->ped_estado)) . '</td>';
            $html .= '</tr>';
        }

        $html .= '<tr style="background-color:#2E7D32; color:#ffffff; font-weight:bold;">';
        $html .= '<td colspan="7" style="text-align:right;">TOTAL DE VENTAS:</td>';
        $html .= '<td colspan="2">$' . number_format($total_ventas, 2) . '</td>';
        $html .= '</tr>';

        $html .= '</table>';

        if (ob_get_length() > 0) { @ob_clean(); }

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('reporte_ventas.pdf', 'D');
        exit;
    }
}
