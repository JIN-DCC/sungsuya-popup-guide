<?php
/**
 * 팝업스토어 Excel 보고서 생성기
 * 
 * PHPSpreadsheet 라이브러리 사용
 * 심플하고 실용적인 Excel 생성
 */

if (!defined('ABSPATH')) {
    exit;
}

// Composer autoload 또는 라이브러리 직접 로드
if (file_exists(ABSPATH . 'vendor/autoload.php')) {
    require_once ABSPATH . 'vendor/autoload.php';
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PopupStoreExcelReporter {
    
    /**
     * Excel 보고서 생성
     */
    public function generate($crawl_results, $filename = null) {
        // PHPSpreadsheet 체크
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // 라이브러리가 없으면 간단한 CSV로 대체
            return $this->generate_simple_csv($crawl_results, $filename);
        }
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('팝업스토어 크롤링 결과');
        
        // 헤더 설정
        $this->set_headers($sheet);
        
        // 데이터 입력
        $row = 2;
        foreach ($crawl_results['items'] as $item) {
            $this->add_data_row($sheet, $row, $item);
            $row++;
        }
        
        // 스타일 적용
        $this->apply_styles($sheet, $row - 1);
        
        // 요약 정보 추가
        $this->add_summary($sheet, $crawl_results['stats'], $row + 2);
        
        // 파일 저장
        $upload_dir = wp_upload_dir();
        $excel_dir = $upload_dir['basedir'] . '/popup-crawl-results';
        
        if (!file_exists($excel_dir)) {
            wp_mkdir_p($excel_dir);
        }
        
        if (!$filename) {
            $filename = 'popup_crawl_' . date('Ymd_Hi') . '.xlsx';
        }
        
        $filepath = $excel_dir . '/' . $filename;
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);
        
        return array(
            'filepath' => $filepath,
            'filename' => $filename,
            'url' => $upload_dir['baseurl'] . '/popup-crawl-results/' . $filename
        );
    }
    
    /**
     * 헤더 설정
     */
    private function set_headers($sheet) {
        $headers = array(
            'A1' => '번호',
            'B1' => '상태',
            'C1' => '출처',
            'D1' => '브랜드명',
            'E1' => '스토어명',
            'F1' => '주소',
            'G1' => '시작일',
            'H1' => '종료일',
            'I1' => '신뢰도',
            'J1' => '설명',
            'K1' => '참고URL',
            'L1' => '발견일시'
        );
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
    }
    
    /**
     * 데이터 행 추가
     */
    private function add_data_row($sheet, $row, $item) {
        $sheet->setCellValue('A' . $row, $row - 1);
        $sheet->setCellValue('B' . $row, $item['status'] === 'new' ? '신규' : '기존');
        $sheet->setCellValue('C' . $row, $item['source']);
        $sheet->setCellValue('D' . $row, $item['brand_name']);
        $sheet->setCellValue('E' . $row, $item['store_name']);
        $sheet->setCellValue('F' . $row, $item['address']);
        $sheet->setCellValue('G' . $row, $item['start_date']);
        $sheet->setCellValue('H' . $row, $item['end_date']);
        $sheet->setCellValue('I' . $row, $item['confidence_score']);
        $sheet->setCellValue('J' . $row, $item['description']);
        $sheet->setCellValue('K' . $row, $item['source_url']);
        $sheet->setCellValue('L' . $row, $item['found_at']);
        
        // 상태에 따른 색상 지정
        if ($item['status'] === 'new') {
            $sheet->getStyle('B' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF90EE90'); // 연한 녹색
        }
        
        // 신뢰도에 따른 색상
        $confidence_cell = 'I' . $row;
        $color = $this->get_confidence_color($item['confidence_score']);
        $sheet->getStyle($confidence_cell)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($color);
    }
    
    /**
     * 신뢰도에 따른 색상 반환
     */
    private function get_confidence_color($score) {
        switch ($score) {
            case 5:
                return 'FF00FF00'; // 밝은 녹색
            case 4:
                return 'FF90EE90'; // 연한 녹색
            case 3:
                return 'FFFFFF00'; // 노란색
            case 2:
                return 'FFFFA500'; // 주황색
            case 1:
                return 'FFFF6347'; // 연한 빨간색
            default:
                return 'FFFFFFFF'; // 흰색
        }
    }
    
    /**
     * 스타일 적용
     */
    private function apply_styles($sheet, $last_row) {
        // 헤더 스타일
        $header_style = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0073AA']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER
            ]
        ];
        
        $sheet->getStyle('A1:L1')->applyFromArray($header_style);
        
        // 테두리 스타일
        $border_style = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000']
                ]
            ]
        ];
        
        $sheet->getStyle('A1:L' . $last_row)->applyFromArray($border_style);
        
        // 컬럼 너비 자동 조정
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // 필터 추가
        $sheet->setAutoFilter('A1:L' . $last_row);
    }
    
    /**
     * 요약 정보 추가
     */
    private function add_summary($sheet, $stats, $start_row) {
        $sheet->setCellValue('A' . $start_row, '📊 크롤링 요약');
        $sheet->mergeCells('A' . $start_row . ':D' . $start_row);
        $sheet->getStyle('A' . $start_row)->getFont()->setBold(true)->setSize(14);
        
        $summary_data = array(
            array('총 발견 항목:', $stats['total'] . '개'),
            array('신규 팝업스토어:', $stats['new'] . '개'),
            array('기존 팝업스토어:', $stats['existing'] . '개'),
            array('오류 발생:', $stats['error'] . '개')
        );
        
        $row = $start_row + 1;
        foreach ($summary_data as $data) {
            $sheet->setCellValue('A' . $row, $data[0]);
            $sheet->setCellValue('B' . $row, $data[1]);
            $sheet->getStyle('B' . $row)->getFont()->setBold(true);
            $row++;
        }
    }
    
    /**
     * PHPSpreadsheet가 없을 때 간단한 CSV 생성
     */
    private function generate_simple_csv($crawl_results, $filename = null) {
        $upload_dir = wp_upload_dir();
        $csv_dir = $upload_dir['basedir'] . '/popup-crawl-results';
        
        if (!file_exists($csv_dir)) {
            wp_mkdir_p($csv_dir);
        }
        
        if (!$filename) {
            $filename = 'popup_crawl_' . date('Ymd_Hi') . '.csv';
        }
        
        $filepath = $csv_dir . '/' . $filename;
        
        $handle = fopen($filepath, 'w');
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        
        // 헤더
        $headers = array('번호', '상태', '출처', '브랜드명', '스토어명', '주소', '시작일', '종료일', '신뢰도', '설명', '참고URL', '발견일시');
        fputcsv($handle, $headers);
        
        // 데이터
        $num = 1;
        foreach ($crawl_results['items'] as $item) {
            $row = array(
                $num++,
                $item['status'] === 'new' ? '신규' : '기존',
                $item['source'],
                $item['brand_name'],
                $item['store_name'],
                $item['address'],
                $item['start_date'],
                $item['end_date'],
                str_repeat('★', $item['confidence_score']) . ' (' . $item['confidence_score'] . '점)',
                $item['description'],
                $item['source_url'],
                $item['found_at']
            );
            fputcsv($handle, $row);
        }
        
        fclose($handle);
        
        return array(
            'filepath' => $filepath,
            'filename' => $filename,
            'url' => $upload_dir['baseurl'] . '/popup-crawl-results/' . $filename
        );
    }
}
