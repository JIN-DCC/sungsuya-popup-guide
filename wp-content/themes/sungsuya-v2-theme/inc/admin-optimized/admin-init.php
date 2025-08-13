<?php
/**
 * 관리자 전용 기능 초기화
 * Phase 2: 관리자 기능 조건부 로딩
 * 
 * 프론트엔드에서는 로딩되지 않아 40% 메모리 절약 효과
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

// 관리자 환경에서만 실행되는지 확인
if (!is_admin()) {
    return;
}

/**
 * 관리자 메뉴 및 페이지 관리
 */
require_once SUNGSUYA_THEME_DIR . '/inc/admin-optimized/admin-menus.php';

/**
 * 관리자 전용 기능들
 */
require_once SUNGSUYA_THEME_DIR . '/inc/admin-optimized/admin-features.php';

/**
 * 프로덕션 배포 도구 (Phase 2에서 조건부 로딩)
 */
require_once SUNGSUYA_THEME_DIR . '/inc/tools/code-cleaner.php';
require_once SUNGSUYA_THEME_DIR . '/inc/tools/database-optimizer.php';
require_once SUNGSUYA_THEME_DIR . '/inc/tools/mobile-image-optimizer.php';
require_once SUNGSUYA_THEME_DIR . '/inc/tools/mobile-performance-tester.php';
require_once SUNGSUYA_THEME_DIR . '/inc/tools/performance-optimizer.php';

/**
 * 관리자 전용 모듈들
 */
require_once SUNGSUYA_THEME_DIR . '/admin/static-map-admin.php';
require_once SUNGSUYA_THEME_DIR . '/inc/admin/thumbnail-manager.php';
require_once SUNGSUYA_THEME_DIR . '/inc/crawlers/crawling-ajax-handlers.php';  // 크롤링 AJAX 핸들러
require_once SUNGSUYA_THEME_DIR . '/inc/crawling/enhanced-crawling-ajax.php';  // Enhanced 크롤링 AJAX
require_once SUNGSUYA_THEME_DIR . '/inc/crawlers/class-popup-store-crawl-scheduler.php'; // 팝업스토어 크롤링 스케줄러
require_once SUNGSUYA_THEME_DIR . '/inc/csv-upload/popup-csv-upload-init.php'; // 팝업 CSV 업로드

/**
 * 신규 추가 관리자 모듈들
 */
require_once SUNGSUYA_THEME_DIR . '/inc/place-detail-auto-collector.php'; // 업종별 상세정보 자동수집
require_once SUNGSUYA_THEME_DIR . '/inc/popup-store-period-manager-enhanced.php'; // 개선된 팝업 기간 관리
require_once SUNGSUYA_THEME_DIR . '/inc/admin/popup-period-dashboard-widget.php'; // 대시보드 위젯
require_once SUNGSUYA_THEME_DIR . '/inc/admin/reorganized-admin-menu.php'; // 정리된 메뉴 시스템
require_once SUNGSUYA_THEME_DIR . '/inc/admin/popup-store-dashboard.php'; // 팝업스토어 통합 대시보드

/**
 * 저작권 안전 이미지 크롤링 시스템
 */
require_once SUNGSUYA_THEME_DIR . '/inc/copyright-safe-image-crawler.php'; // 저작권 안전 이미지 크롤러
require_once SUNGSUYA_THEME_DIR . '/admin/copyright-safe-image-admin.php'; // 관리자 페이지

/**
 * 업종별 상세정보 자동수집 시스템
 */
require_once SUNGSUYA_THEME_DIR . '/inc/place-detail-auto-collector.php'; // 상세정보 자동수집 클래스
require_once SUNGSUYA_THEME_DIR . '/admin/place-detail-collector-admin.php'; // 관리자 페이지
require_once SUNGSUYA_THEME_DIR . '/inc/admin/popup-store-ui-tabs.php'; // 팝업스토어 탭 UI 개선
require_once SUNGSUYA_THEME_DIR . '/inc/admin/places-menu-map-generation.php'; // 장소 메뉴 지도생성 복구

/**
 * 통합 이미지 크롤링 시스템 (가장 최신 버전)
 */
require_once SUNGSUYA_THEME_DIR . '/inc/accurate-image-crawler.php'; // 정확도 개선된 이미지 크롤러 V2

/**
 * 관리자 UX 개선 모듈들
 */
require_once SUNGSUYA_THEME_DIR . '/inc/admin/menu-cleanup.php';              // 메뉴 중복 제거
require_once SUNGSUYA_THEME_DIR . '/inc/admin/places-list-improvements.php'; // Places 목록 개선
require_once SUNGSUYA_THEME_DIR . '/inc/admin/notification-system.php';      // 알림 시스템
require_once SUNGSUYA_THEME_DIR . '/inc/admin/clean-admin-styles.php';       // 깔끔한 스타일
require_once SUNGSUYA_THEME_DIR . '/inc/admin/workflow-improvements.php';    // 워크플로우 개선
require_once SUNGSUYA_THEME_DIR . '/inc/admin/help-system.php';              // 도움말 시스템
require_once SUNGSUYA_THEME_DIR . '/inc/admin/dashboard-improvements.php';   // 대시보드 개선

/**
 * 관리자 UI 커스터마이징
 */
require_once SUNGSUYA_THEME_DIR . '/inc/admin/admin-customization.php';

/**
 * 유연한 장소유형 시스템
 */
require_once SUNGSUYA_THEME_DIR . '/inc/admin/place-type-meta.php';          // 장소유형 메타필드

/**
 * 관리자 기능 중 일부만 조건부 로딩
 */
require_once SUNGSUYA_THEME_DIR . '/inc/admin/batch-geocoding-processor.php';           // 배치 지오코딩
require_once SUNGSUYA_THEME_DIR . '/inc/admin/integrated-map-generation-system.php';    // 통합 지도생성
require_once SUNGSUYA_THEME_DIR . '/inc/admin/bulk-crawling-admin-improved.php';       // 대량크롤링 (개선된 UI/UX)
