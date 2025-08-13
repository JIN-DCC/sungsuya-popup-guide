<?php
/**
 * 🗺️ 통합 지도생성 시스템 - 관리자 페이지 (간소화 버전)
 * 
 * @package SungsuyaV2
 * @version 2.0.0
 * @since 2025-06-25
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}
?>

<div class="wrap integrated-map-system">
    <h1>🗺️ 통합 지도생성 시스템</h1>
    
    <div class="system-description">
        <p><strong>중앙집중식 지도 처리 시스템</strong>으로 모든 Places의 좌표를 일괄 관리합니다.</p>
        <p>주소가 입력된 Places를 자동으로 감지하여 네이버 지오코딩 API로 정확한 좌표를 생성하고, 정적지도까지 한 번에 처리합니다.</p>
    </div>

    <!-- 상태 요약 -->
    <div class="status-summary">
        <div class="status-card">
            <h3>좌표 미확정</h3>
            <div class="status-number" data-type="pending">0</div>
            <div class="status-label">처리 대기 중</div>
        </div>
        
        <div class="status-card">
            <h3>좌표 완료</h3>
            <div class="status-number" data-type="completed">0</div>
            <div class="status-label">처리 완료</div>
        </div>
        
        <div class="status-card">
            <h3>전체 Places</h3>
            <div class="status-number" data-type="total">0</div>
            <div class="status-label">총 장소 수</div>
        </div>
    </div>

    <!-- 액션 버튼 -->
    <div class="action-buttons">
        <button type="button" id="start-batch-processing" class="btn-primary">
            🚀 일괄 지오코딩 시작
        </button>
        
        <button type="button" id="refresh-data" class="btn-secondary">
            🔄 데이터 새로고침
        </button>
    </div>

    <!-- 진행상황 -->
    <div class="progress-section" id="progress-section">
        <div class="progress-header">
            <h3 class="progress-title">배치 처리 진행상황</h3>
            <span class="progress-percentage">0%</span>
        </div>
        
        <div class="progress-bar-container">
            <div class="progress-bar" id="progress-bar"></div>
        </div>
        
        <div class="progress-log" id="progress-log">
            [시작] 배치 처리가 시작되면 진행상황이 여기에 표시됩니다...
        </div>
    </div>

    <!-- Places 목록 테이블 -->
    <div class="places-table-container">
        <h3>📋 처리 대상 Places 목록</h3>
        <table class="places-table">
            <thead>
                <tr>
                    <th>장소명</th>
                    <th>주소</th>
                    <th>카테고리</th>
                    <th>좌표 상태</th>
                    <th>액션</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px;">
                        데이터를 불러오는 중...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.progress-log {
    background: #23282d;
    color: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    font-size: 11px;
    line-height: 1.4;
    max-height: 300px;
    overflow-y: auto;
    white-space: pre-wrap;
    word-wrap: break-word;
    margin-top: 10px;
}

@media (max-width: 768px) {
    .status-summary {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🗺️ 통합 지도생성 시스템 관리자 페이지 로드 완료');
});
</script>
