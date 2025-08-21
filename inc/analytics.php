<?php
/**
 * Google Analytics 4 다국어 통합
 * 성수야! V2 - 언어별 트래킹 시스템
 */

// Google Analytics 설정
function sungsuya_add_google_analytics() {
    // GA4 측정 ID (실제 배포 시 변경 필요)
    $ga_measurement_id = 'G-XXXXXXXXXX'; // 실제 GA4 ID로 변경
    
    // 현재 언어 감지
    $current_language = 'ko'; // 기본값
    if (function_exists('trp_get_current_language')) {
        $current_language = trp_get_current_language();
    }
    
    // 페이지 정보 수집
    $page_info = array(
        'language' => $current_language,
        'page_type' => get_page_type(),
        'tour_step' => get_tour_step(),
        'user_type' => is_user_logged_in() ? 'logged_in' : 'guest'
    );
    
    ?>
    <!-- Google Analytics 4 (다국어 지원) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $ga_measurement_id; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        
        // 기본 GA4 설정
        gtag('config', '<?php echo $ga_measurement_id; ?>', {
            'language': '<?php echo $current_language; ?>',
            'custom_map': {
                'custom_parameter_1': 'language',
                'custom_parameter_2': 'page_type',
                'custom_parameter_3': 'tour_step'
            }
        });
        
        // 언어별 세그먼트 설정
        gtag('config', '<?php echo $ga_measurement_id; ?>', {
            'custom_map': {
                'site_language': '<?php echo $current_language; ?>',
                'page_category': '<?php echo $page_info['page_type']; ?>'
            }
        });
        
        // 커스텀 이벤트 추적 함수들
        window.trackTourEvent = function(action, tourData) {
            gtag('event', action, {
                'event_category': 'tour_planner',
                'event_label': '<?php echo $current_language; ?>',
                'language': '<?php echo $current_language; ?>',
                'tour_places_count': tourData.placesCount || 0,
                'tour_duration': tourData.duration || 0,
                'custom_parameter_1': '<?php echo $current_language; ?>'
            });
            if (window.debugLog) {
                window.debugLog('GA Event tracked:', action, tourData);
            }
        };
        
        window.trackLanguageSwitch = function(fromLang, toLang) {
            gtag('event', 'language_switch', {
                'event_category': 'internationalization',
                'event_label': fromLang + '_to_' + toLang,
                'from_language': fromLang,
                'to_language': toLang,
                'custom_parameter_1': toLang
            });
            if (window.debugLog) {
                window.debugLog('Language switch tracked:', fromLang, '->', toLang);
            }
        };
        
        window.trackPlaceInteraction = function(action, placeData) {
            gtag('event', action, {
                'event_category': 'place_interaction',
                'event_label': '<?php echo $current_language; ?>',
                'place_type': placeData.type || 'unknown',
                'place_name': placeData.name || 'unknown',
                'language': '<?php echo $current_language; ?>'
            });
        };
        
        // 페이지뷰 이벤트 (언어별)
        gtag('event', 'page_view', {
            'event_category': 'page_interaction',
            'event_label': '<?php echo $current_language; ?>',
            'page_type': '<?php echo $page_info['page_type']; ?>',
            'language': '<?php echo $current_language; ?>',
            'user_type': '<?php echo $page_info['user_type']; ?>'
        });
        
        // 성수야! 전용 이벤트들
        window.sungsuyaAnalytics = {
            // 투어 시작
            tourStarted: function(tourType) {
                trackTourEvent('tour_started', {
                    tourType: tourType,
                    placesCount: 0
                });
            },
            
            // 장소 추가
            placeAdded: function(placeData) {
                trackPlaceInteraction('place_added', placeData);
            },
            
            // 장소 제거  
            placeRemoved: function(placeData) {
                trackPlaceInteraction('place_removed', placeData);
            },
            
            // 투어 완성
            tourCompleted: function(tourData) {
                trackTourEvent('tour_completed', tourData);
            },
            
            // 경로 최적화
            routeOptimized: function(tourData) {
                trackTourEvent('route_optimized', tourData);
            },
            
            // 투어 가이드 생성
            guideGenerated: function(tourData) {
                trackTourEvent('guide_generated', tourData);
            },
            
            // 투어 공유
            tourShared: function(shareMethod, tourData) {
                gtag('event', 'tour_shared', {
                    'event_category': 'sharing',
                    'event_label': '<?php echo $current_language; ?>',
                    'share_method': shareMethod,
                    'language': '<?php echo $current_language; ?>',
                    'tour_places_count': tourData.placesCount || 0
                });
            }
        };
        
        if (window.debugLog) {
            window.debugLog('🔍 Sungsuya Analytics initialized for language: <?php echo $current_language; ?>');
        }
    </script>
    
    <!-- 실시간 사용자 추적 (개발 모드) -->
    <?php if (WP_DEBUG) : ?>
    <script>
        // 개발 모드에서 실시간 로깅
        if (window.debugLog) {
            window.debugLog('📊 GA Debug Info:', {
                measurementId: '<?php echo $ga_measurement_id; ?>',
                language: '<?php echo $current_language; ?>',
                pageType: '<?php echo $page_info['page_type']; ?>',
                userType: '<?php echo $page_info['user_type']; ?>'
            });
        }
    </script>
    <?php endif; ?>
    <?php
}

// 페이지 타입 감지 함수
function get_page_type() {
    if (is_front_page()) {
        return 'homepage';
    } elseif (is_page('planner')) {
        return 'tour_planner';
    } elseif (is_post_type_archive('places')) {
        return 'places_archive';
    } elseif (is_singular('places')) {
        return 'place_single';
    } elseif (is_post_type_archive('popup_store')) {
        return 'popup_stores';
    } elseif (is_singular('popup_store')) {
        return 'popup_store_single';
    } else {
        return 'other';
    }
}

// 투어 단계 감지 함수
function get_tour_step() {
    if (is_page('planner')) {
        return isset($_GET['step']) ? sanitize_text_field($_GET['step']) : 'selection';
    }
    return 'none';
}

// 언어 전환 추적 JavaScript 추가
function sungsuya_add_language_tracking() {
    if (!function_exists('trp_get_languages')) return;
    
    ?>
    <script>
        // 언어 전환 버튼 클릭 추적
        document.addEventListener('DOMContentLoaded', function() {
            const languageSwitcher = document.querySelector('.language-switcher');
            if (languageSwitcher) {
                languageSwitcher.addEventListener('click', function(e) {
                    const link = e.target.closest('a');
                    if (link) {
                        const currentLang = '<?php echo trp_get_current_language(); ?>';
                        const targetLang = link.getAttribute('href').split('/')[3] || 'ko';
                        
                        if (window.trackLanguageSwitch) {
                            window.trackLanguageSwitch(currentLang, targetLang);
                        }
                    }
                });
            }
            
            // 투어플래너 이벤트 자동 추적
            if (window.pwaTourPlanner) {
                const originalSelectTourPurpose = window.pwaTourPlanner.selectTourPurpose;
                window.pwaTourPlanner.selectTourPurpose = function(purpose) {
                    if (window.sungsuyaAnalytics) {
                        window.sungsuyaAnalytics.tourStarted(purpose);
                    }
                    return originalSelectTourPurpose.call(this, purpose);
                };
                
                const originalRemovePlace = window.pwaTourPlanner.removePlace;
                window.pwaTourPlanner.removePlace = function(placeId) {
                    const place = this.selectedPlaces.find(p => p.id == placeId);
                    if (place && window.sungsuyaAnalytics) {
                        window.sungsuyaAnalytics.placeRemoved({
                            name: place.title,
                            type: place.place_type
                        });
                    }
                    return originalRemovePlace.call(this, placeId);
                };
            }
        });
    </script>
    <?php
}

// WordPress 훅에 연결
add_action('wp_head', 'sungsuya_add_google_analytics');
add_action('wp_footer', 'sungsuya_add_language_tracking');
