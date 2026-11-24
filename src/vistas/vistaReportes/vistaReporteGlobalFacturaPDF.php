<?php

$facturas = (isset($facturas) && is_array($facturas)) ? $facturas : [];
$servicios = (isset($servicios) && is_array($servicios)) ? $servicios : [];
$periodo = $periodo ?? 'TODAS LAS FECHAS';
$doctorFiltro = $doctorFiltro ?? 'TODOS LOS DOCTORES';
$estadoFiltro = $estadoFiltro ?? 'TODAS (ACTIVAS Y ANULADAS)';

class ReporteGlobalFacturacionPDF extends FPDF
{
    private function aLatin1($valor)
    {
        $valor = (string)($valor ?? '');

        if (function_exists('mb_convert_encoding')) {
            if (!function_exists('mb_check_encoding') || mb_check_encoding($valor, 'UTF-8')) {
                return mb_convert_encoding($valor, 'ISO-8859-1', 'UTF-8');
            }
            return $valor;
        }

        return function_exists('utf8_decode') ? utf8_decode($valor) : $valor;
    }

    private function ajustar($texto, $ancho)
    {
        $texto = $this->aLatin1($texto);
        $limite = max(1, $ancho - 2);

        while ($texto !== '' && $this->GetStringWidth($texto) > $limite) {
            $texto = substr($texto, 0, -1);
        }

        return $texto;
    }

    private function hayEspacio($alto)
    {
        return ($this->GetY() + $alto) <= $this->PageBreakTrigger;
    }

    public function Header()
    {
        $this->Image('./src/assets/images/image/depositphotos_87603960-stock-illustration-soft-bending-line-blue-sky-transformed.jpeg', 0, -53, 350);
        $this->Image('./src/assets/images/image/123.png', 10, -13, 80);

        $this->SetFont('Arial', 'B', 22);
        $this->SetTextColor(14, 169, 181);
        $this->SetXY(100.2, 16.5);
        $this->Cell(100, 12, $this->aLatin1('REPORTE DE FACTURACIÓN'), 0, 1, 'L');

        $this->SetY(30);
    }

    public function Filtros($periodo, $doctor, $estado)
    {
        $etiquetas = ['PERIODO:', 'DOCTOR:', 'ESTADO:'];
        $valores = [$periodo, $doctor, $estado];

        for ($i = 0; $i < 3; $i++) {
            $y = 36 + ($i * 9);

            $this->SetFont('Arial', 'B', 15);
            $this->SetTextColor(14, 169, 181);
            $this->SetXY(21, $y);
            $this->Cell(40, 9, $this->aLatin1($etiquetas[$i]), 0, 0, 'L');

            $this->SetFont('Arial', '', 15);
            $this->SetXY(61, $y);
            $this->Cell(0, 9, $this->aLatin1($valores[$i]), 0, 1, 'L');
        }

        $this->SetY(68);
    }

    public function Seccion($titulo)
    {
        $y = $this->GetY();

        $this->SetFillColor(14, 169, 181);
        $this->Rect(10, $y, 190, 9, 'F');

        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(10, $y);
        $this->Cell(190, 9, $this->aLatin1($titulo), 0, 1, 'C');

        $this->SetY($y + 9);
    }

    private function filaEncabezado($columnas)
    {
        $y = $this->GetY();

        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(255, 255, 255);
        $this->SetFillColor(14, 169, 181);

        foreach ($columnas as $columna) {
            $this->SetXY($columna[0], $y);
            $this->Cell($columna[1], 8, $this->aLatin1($columna[2]), 0, 0, $columna[3], true);
        }

        $this->SetY($y + 8);
    }

    private function filaVacia($x, $ancho, $texto)
    {
        $y = $this->GetY();

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(0, 0, 0);
        $this->SetXY($x, $y);
        $this->Cell($ancho, 8, $this->ajustar($texto, $ancho), 0, 0, 'C');

        $this->SetY($y + 8);
    }

    private function filaFactura($factura, $relleno)
    {
        $estado = (string)($factura['estado'] ?? '');
        $estadoTexto = $estado === 'ACT' ? 'ACTIVA' : ($estado === 'Anulada' ? 'ANULADA' : strtoupper($estado));

        $doctor = trim((string)($factura['doctor'] ?? ''));
        if ($doctor === '') {
            $doctor = 'Sin doctor asignado';
        }

        $valores = [
            [15, 24, 'numeroFactura'],
            [39, 20, 'fecha'],
            [59, 36, 'paciente'],
            [95, 24, 'cedula'],
            [119, 40, 'doctor'],
            [159, 15, 'estado'],
            [174, 25, 'monto'],
        ];

        $textos = [
            'numeroFactura' => numeroFacturaLegible((int)$factura['id_factura']),
            'fecha' => (string)$factura['fecha'],
            'paciente' => trim($factura['nombre_p'] . ' ' . $factura['apellido_p']),
            'cedula' => $factura['nacionalidad'] . '-' . $factura['cedula_p'],
            'doctor' => $doctor,
            'estado' => $estadoTexto,
            'monto' => number_format((float)$factura['total'], 2, '.', '') . ' BS',
        ];

        $alineaciones = [
            'numeroFactura' => 'C',
            'fecha' => 'C',
            'paciente' => 'C',
            'cedula' => 'C',
            'doctor' => 'C',
            'estado' => 'C',
            'monto' => 'R',
        ];

        $y = $this->GetY();

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(0, 0, 0);
        if ($relleno) {
            $this->SetFillColor(228, 235, 240);
        }

        foreach ($valores as $valor) {
            $texto = $valor[2] === 'estado'
                ? $this->aLatin1($textos[$valor[2]])
                : $this->ajustar($textos[$valor[2]], $valor[1]);

            $this->SetXY($valor[0], $y);
            $this->Cell(
                $valor[1],
                8,
                $texto,
                0,
                0,
                $alineaciones[$valor[2]],
                $relleno
            );
        }

        $this->SetY($y + 8);
    }

    public function TablaFacturas($facturas)
    {
        $this->Seccion('LISTADO DE FACTURAS');
        $this->Ln(3);

        $columnas = [
            [15, 24, 'CODIGO', 'C'],
            [39, 20, 'FECHA', 'C'],
            [59, 36, 'PACIENTE', 'C'],
            [95, 24, 'CEDULA', 'C'],
            [119, 40, 'DOCTOR', 'C'],
            [159, 15, 'ESTADO', 'C'],
            [174, 25, 'MONTO', 'C'],
        ];

        $this->filaEncabezado($columnas);

        if (empty($facturas)) {
            $this->filaVacia(15, 184, 'No hay facturas para los filtros seleccionados.');
            return;
        }

        $indice = 0;
        foreach ($facturas as $factura) {
            if (!$this->hayEspacio(8)) {
                $this->AddPage();
                $this->Seccion('LISTADO DE FACTURAS (CONTINUACIÓN)');
                $this->Ln(3);
                $this->filaEncabezado($columnas);
                $indice = 0;
            }

            $this->filaFactura($factura, ($indice % 2) === 1);
            $indice++;
        }
    }

    public function Metricas($facturas)
    {
        $totalActivas = 0.0;
        $totalAnuladas = 0.0;
        $totalGeneral = 0.0;
        $cantidadActivas = 0;
        $cantidadAnuladas = 0;
        $clientes = [];

        foreach ($facturas as $factura) {
            $total = (float)$factura['total'];
            $totalGeneral += $total;

            if (($factura['estado'] ?? '') === 'ACT') {
                $cantidadActivas++;
                $totalActivas += $total;
            } else {
                $cantidadAnuladas++;
                $totalAnuladas += $total;
            }

            $clientes[(string)$factura['id_cliente']] = true;
        }

        $this->Ln(6);
        if (!$this->hayEspacio(65)) {
            $this->AddPage();
            $this->SetY(30);
        }

        $this->Seccion('METRICAS Y TOTALES');
        $this->Ln(1);

        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(14, 169, 181);

        $lineas = [
            'PACIENTES ATENDIDOS:  ' . count($clientes),
            'FACTURAS EMITIDAS:  ' . count($facturas) . '   (ACTIVAS: ' . $cantidadActivas . ' / ANULADAS: ' . $cantidadAnuladas . ')',
            'TOTAL FACTURAS ACTIVAS:  ' . number_format($totalActivas, 2, '.', '') . ' BS',
            'TOTAL FACTURAS ANULADAS:  ' . number_format($totalAnuladas, 2, '.', '') . ' BS',
            'TOTAL GENERAL FACTURADO:  ' . number_format($totalGeneral, 2, '.', '') . ' BS',
        ];

        foreach ($lineas as $linea) {
            $this->SetX(21);
            $this->Cell(0, 9, $this->aLatin1($linea), 0, 1, 'L');
        }
    }

    public function TablaServicios($servicios)
    {
        $this->Ln(5);
        if (!$this->hayEspacio(45)) {
            $this->AddPage();
            $this->SetY(30);
        }

        $this->Seccion('RESUMEN DE SERVICIOS');
        $this->Ln(3);

        $columnas = [
            [10, 110, 'SERVICIO', 'C'],
            [120, 40, 'VECES PRESTADO', 'C'],
            [160, 40, 'MONTO (BS)', 'C'],
        ];

        $this->filaEncabezado($columnas);

        if (empty($servicios)) {
            $this->filaVacia(10, 190, 'Sin servicios registrados para los filtros seleccionados.');
            return;
        }

        $indice = 0;
        foreach ($servicios as $servicio) {
            if (!$this->hayEspacio(8)) {
                $this->AddPage();
                $this->Seccion('RESUMEN DE SERVICIOS (CONTINUACIÓN)');
                $this->Ln(3);
                $this->filaEncabezado($columnas);
                $indice = 0;
            }

            $y = $this->GetY();
            $relleno = ($indice % 2) === 1;

            $this->SetFont('Arial', '', 9);
            $this->SetTextColor(0, 0, 0);
            if ($relleno) {
                $this->SetFillColor(228, 235, 240);
            }

            $valores = [
                [10, 110, (string)$servicio['servicio'], 'L'],
                [120, 40, (string)(int)$servicio['veces'], 'C'],
                [160, 40, number_format((float)$servicio['monto'], 2, '.', '') . ' BS', 'R'],
            ];

            foreach ($valores as $valor) {
                $this->SetXY($valor[0], $y);
                $this->Cell($valor[1], 8, $this->ajustar($valor[2], $valor[1]), 0, 0, $valor[3], $relleno);
            }

            $this->SetY($y + 8);
            $indice++;
        }
    }
}

$pdf = new ReporteGlobalFacturacionPDF('P', 'mm', 'A4');
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();
$pdf->Filtros($periodo, $doctorFiltro, $estadoFiltro);
$pdf->TablaFacturas($facturas);
$pdf->Metricas($facturas);
$pdf->TablaServicios($servicios);

ob_end_clean();
$pdf->Output();
exit;
