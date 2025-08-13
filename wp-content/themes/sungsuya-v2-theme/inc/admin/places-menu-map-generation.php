<?php
/**
 * 장소 메뉴에 지도생성 메뉴 복구
 * 
 * @package SungsuyaV2
 * @since 2.1.0
 */

// 장소 메뉴에 지도생성 서브메뉴 추가
add_action('admin_menu', 'add_map_generation_to_places_menu', 15);
function add_map_generation_to_places_menu() {
    // 통합 지도생성 시스템 메뉴 추가
    add_submenu_page(
        'edit.php?post_type=places',
        '지도 생성',
        '🗺️ 지도 생성',
        'manage_options',
        'integrated-map-generation',
        'sungsuya_integrated_map_generation_page'
    );
}

// 페이지 렌더링 함수 정의
function sungsuya_integrated_map_generation_page() {
    // IntegratedMapGenerationSystem 클래스의 인스턴스를 통해 페이지 렌더링
    $system = new IntegratedMapGenerationSystem();
    $system->render_admin_page();
}
