<?php
/**
 * 업종별 장소 상세정보 자동수집 시스템 (수정된 버전)
 * 
 * 네이버, 카카오, 구글 API를 통해 업종별 상세정보를 자동으로 수집합니다.
 * 
 * @package SungsuyaV2
 * @version 1.0.1
 * @since 2025-01-02
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 장소 상세정보 자동수집 클래스
 */
class Place_Detail_Auto_Collector {
    
    /**
     * 싱글톤 인스턴스
     */
    private static $instance = null;
    
    /**
     * API 클라이언트
     */
    private $naver_client;
    private $kakao_client;
    private $google_client;
    
    /**
     * 업종별 수집 필드 매핑
     */
    private $field_mappings = array(
        'food' => array(
            'menu_items' => array('naver' => 'menuInfo', 'kakao' => 'menu', 'google' => 'menu'),
            'price_range' => array('naver' => 'priceLevel', 'kakao' => 'price_level', 'google' => 'price_level'),
            'cuisine_type' => array('naver' => 'category', 'kakao' => 'category_name', 'google' => 'types'),
            'opening_hours' => array('naver' => 'businessHours', 'kakao' => 'opening_hours', 'google' => 'opening_hours'),
            'break_time' => array('naver' => 'breakTime', 'kakao' => 'break_time'),
            'last_order' => array('naver' => 'lastOrder', 'kakao' => 'last_order'),
            'reservation' => array('naver' => 'reservationAvailable', 'kakao' => 'reservation', 'google' => 'reservable'),
            'delivery' => array('naver' => 'deliveryAvailable', 'kakao' => 'delivery'),
            'takeout' => array('naver' => 'takeoutAvailable', 'kakao' => 'takeout', 'google' => 'takeout')
        ),
        'shop' => array(
            'brands' => array('naver' => 'brands', 'kakao' => 'brands'),
            'product_category' => array('naver' => 'category', 'kakao' => 'category_name', 'google' => 'types'),
            'price_level' => array('naver' => 'priceLevel', 'kakao' => 'price_level', 'google' => 'price_level'),
            'opening_hours' => array('naver' => 'businessHours', 'kakao' => 'opening_hours', 'google' => 'opening_hours'),
            'special_services' => array('naver' => 'services', 'kakao' => 'service'),
            'payment_methods' => array('naver' => 'paymentMethods', 'kakao' => 'payment'),
            'online_shop' => array('naver' => 'onlineShop', 'kakao' => 'homepage', 'google' => 'website'),
            'parking' => array('naver' => 'parking', 'kakao' => 'parking', 'google' => 'parking')
        ),
        'cafe' => array(
            'signature_menu' => array('naver' => 'signatureMenu', 'kakao' => 'signature_menu'),
            'coffee_beans' => array('naver' => 'coffeeBeans', 'kakao' => 'coffee_info.beans'),
            'roasting' => array('naver' => 'roasting', 'kakao' => 'coffee_info.roasting'),
            'decaf_available' => array('naver' => 'decafAvailable', 'kakao' => 'decaf'),
            'desserts' => array('naver' => 'desserts', 'kakao' => 'dessert_menu'),
            'wifi' => array('naver' => 'wifi', 'kakao' => 'wifi', 'google' => 'wifi'),
            'power_outlets' => array('naver' => 'powerOutlets', 'kakao' => 'outlet'),
            'pet_friendly' => array('naver' => 'petFriendly', 'kakao' => 'pet', 'google' => 'allows_pets'),
            'study_friendly' => array('naver' => 'studyFriendly', 'kakao' => 'study'),
            'noise_level' => array('naver' => 'noiseLevel', 'kakao' => 'noise_level')
        ),
        'restaurant' => array(
            'cuisine_detail' => array('naver' => 'cuisineDetail', 'kakao' => 'food_type'),
            'signature_dishes' => array('naver' => 'signatureDishes', 'kakao' => 'best_menu'),
            'course_menu' => array('naver' => 'courseMenu', 'kakao' => 'course'),
            'vegetarian_options' => array('naver' => 'vegetarianOptions', 'kakao' => 'vegetarian', 'google' => 'serves_vegetarian_food'),
            'alcohol_served' => array('naver' => 'alcoholServed', 'kakao' => 'alcohol', 'google' => 'serves_alcohol'),
            'private_rooms' => array('naver' => 'privateRooms', 'kakao' => 'room'),
            'group_seating' => array('naver' => 'groupSeating', 'kakao' => 'group_available'),
            'kids_menu' => array('naver' => 'kidsMenu', 'kakao' => 'kids_menu', 'google' => 'kids_menu'),
            'lunch_special' => array('naver' => 'lunchSpecial', 'kakao' => 'lunch_special'),
            'corkage' => array('naver' => 'corkage', 'kakao' => 'corkage_charge')
        )
    );
    
    /**
     * 생성자
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * 싱글톤 인스턴스 반환
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 초기화
     */
    private function init() {
        // AJAX 핸들러 등록
        add_action('wp_ajax_collect_place_details', array($this, 'ajax_collect_place_details'));
        add_action('wp_ajax_test_api_availability', array($this, 'ajax_test_api_availability'));
        
        // 메타박스에 자동수집 버튼 추가
        add_action('admin_footer', array($this, 'add_collection_button_script'));
        
        // 관리자 메뉴 추가
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * 관리자 메뉴 추가
     */
    public function add_admin_menu() {
        add_submenu_page(
            'sungsuya-management',
            '상세정보 자동수집',
            '🤖 상세정보 자동수집',
            'manage_options',
            'place-detail-auto-collector',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * 관리자 페이지 렌더링
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><span class="dashicons dashicons-download"></span> 업종별 상세정보 자동수집</h1>
            
            <div class="notice notice-info">
                <p>네이버, 카카오, 구글 API를 통해 장소의 상세정보를 자동으로 수집합니다.</p>
            </div>
            
            <!-- API 상태 확인 -->
            <div class="card">
                <h2>API 상태</h2>
                <div id="api-status">
                    <p>API 상태를 확인하는 중...</p>
                </div>
                <button type="button" class="button" id="test-api">API 테스트</button>
            </div>
            
            <!-- 개별 수집 -->
            <div class="card">
                <h2>개별 장소 수집</h2>
                <form id="individual-collection">
                    <table class="form-table">
                        <tr>
                            <th>장소 선택</th>
                            <td>
                                <select id="place-select" name="post_id">
                                    <option value="">장소를 선택하세요</option>
                                    <?php
                                    $places = get_posts(array(
                                        'post_type' => 'places',
                                        'posts_per_page' => -1,
                                        'orderby' => 'title',
                                        'order' => 'ASC'
                                    ));
                                    
                                    foreach ($places as $place) {
                                        $address = get_post_meta($place->ID, 'address', true);
                                        echo '<option value="' . $place->ID . '">' . 
                                             $place->post_title . ' (' . $address . ')</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>수집 옵션</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="overwrite" value="1">
                                    기존 데이터 덮어쓰기
                                </label>
                            </td>
                        </tr>
                    </table>
                    <button type="submit" class="button button-primary">상세정보 수집</button>
                </form>
                <div id="collection-result" style="margin-top: 20px;"></div>
            </div>
            
            <!-- 일괄 수집 -->
            <div class="card">
                <h2>일괄 수집</h2>
                <p>모든 장소의 상세정보를 한 번에 수집합니다.</p>
                <button type="button" class="button" id="bulk-collect">전체 장소 일괄 수집</button>
                <div id="bulk-progress" style="margin-top: 20px;"></div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // API 상태 확인
            function checkApiStatus() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_api_availability',
                        nonce: '<?php echo wp_create_nonce('test_api_availability'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '<table class="widefat">';
                            html += '<tr><th>API</th><th>상태</th><th>메시지</th></tr>';
                            
                            $.each(response.data, function(api, result) {
                                var statusClass = result.status === 'success' ? 'notice-success' : 'notice-error';
                                html += '<tr>';
                                html += '<td>' + api.toUpperCase() + ' API</td>';
                                html += '<td><span class="notice ' + statusClass + '" style="padding: 5px;">' + 
                                       (result.status === 'success' ? '정상' : '오류') + '</span></td>';
                                html += '<td>' + result.message + '</td>';
                                html += '</tr>';
                            });
                            
                            html += '</table>';
                            $('#api-status').html(html);
                        }
                    }
                });
            }
            
            // 페이지 로드 시 API 상태 확인
            checkApiStatus();
            
            // API 테스트 버튼
            $('#test-api').on('click', function() {
                $(this).prop('disabled', true).text('테스트 중...');
                checkApiStatus();
                $(this).prop('disabled', false).text('API 테스트');
            });
            
            // 개별 수집
            $('#individual-collection').on('submit', function(e) {
                e.preventDefault();
                
                var post_id = $('#place-select').val();
                if (!post_id) {
                    alert('장소를 선택해주세요.');
                    return;
                }
                
                var $button = $(this).find('button[type="submit"]');
                $button.prop('disabled', true).text('수집 중...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'collect_place_details',
                        post_id: post_id,
                        overwrite: $('input[name="overwrite"]').is(':checked'),
                        nonce: '<?php echo wp_create_nonce('collect_place_details'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '<div class="notice notice-success"><p>' + response.data.message + '</p>';
                            
                            if (response.data.fields.length > 0) {
                                html += '<h4>수집된 필드:</h4><ul>';
                                $.each(response.data.fields, function(i, field) {
                                    html += '<li><strong>' + field.label + ':</strong> ' + field.value + '</li>';
                                });
                                html += '</ul>';
                            }
                            
                            html += '</div>';
                            $('#collection-result').html(html);
                        } else {
                            $('#collection-result').html(
                                '<div class="notice notice-error"><p>' + response.data + '</p></div>'
                            );
                        }
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('상세정보 수집');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * 장소 상세정보 수집 메인 함수
     */
    public function collect_place_details($post_id, $options = array()) {
        $place_name = get_the_title($post_id);
        $address = get_post_meta($post_id, 'address', true);
        $place_type = $this->get_place_metafield_type($post_id);
        
        // 옵션 기본값 설정
        $options = wp_parse_args($options, array(
            'sources' => array('naver', 'kakao', 'google'),
            'overwrite' => false,
            'use_cache' => true
        ));
        
        error_log("=== 장소 상세정보 자동수집 시작 ===");
        error_log("장소: {$place_name}, 주소: {$address}, 타입: {$place_type}");
        
        // 캐시 확인
        if ($options['use_cache']) {
            $cached = get_post_meta($post_id, '_auto_collected_details_cache', true);
            if ($cached && !empty($cached['data']) && $cached['timestamp'] > time() - DAY_IN_SECONDS) {
                error_log("캐시된 상세정보 사용");
                return $cached['data'];
            }
        }
        
        $collected_data = array();
        
        // 각 소스에서 데이터 수집
        foreach ($options['sources'] as $source) {
            $source_data = $this->collect_from_source($source, $place_name, $address, $place_type);
            if (!empty($source_data)) {
                $collected_data[$source] = $source_data;
            }
        }
        
        // 수집된 데이터 병합 및 정제
        $merged_data = $this->merge_and_refine_data($collected_data, $place_type);
        
        // 기존 데이터와 병합
        if (!$options['overwrite']) {
            $merged_data = $this->merge_with_existing_data($post_id, $merged_data);
        }
        
        // 데이터 저장
        $this->save_collected_data($post_id, $merged_data);
        
        // 캐시 저장
        if ($options['use_cache']) {
            update_post_meta($post_id, '_auto_collected_details_cache', array(
                'data' => $merged_data,
                'timestamp' => time()
            ));
        }
        
        error_log("수집 완료. 총 " . count($merged_data) . "개 필드 수집됨");
        
        return $merged_data;
    }
    
    /**
     * 특정 소스에서 데이터 수집
     */
    private function collect_from_source($source, $place_name, $address, $place_type) {
        error_log("수집 소스: {$source}");
        
        switch ($source) {
            case 'naver':
                return $this->collect_from_naver($place_name, $address, $place_type);
                
            case 'kakao':
                return $this->collect_from_kakao($place_name, $address, $place_type);
                
            case 'google':
                return $this->collect_from_google($place_name, $address, $place_type);
                
            default:
                return array();
        }
    }
    
    /**
     * 네이버에서 데이터 수집 (수정된 버전)
     */
    private function collect_from_naver($place_name, $address, $place_type) {
        $data = array();
        
        $client_id = get_option('sungsuya_naver_client_id', '');
        $client_secret = get_option('sungsuya_naver_client_secret', '');
        
        if (empty($client_id) || empty($client_secret)) {
            error_log("네이버 API 키 없음");
            return $data;
        }
        
        // 네이버 개발자 센터 지역 검색 API 사용
        $search_query = $place_name . ' ' . $address;
        $url = 'https://openapi.naver.com/v1/search/local.json';
        $url .= '?query=' . urlencode($search_query);
        $url .= '&display=5';
        
        error_log("네이버 API URL: " . $url);
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'X-Naver-Client-Id' => $client_id,
                'X-Naver-Client-Secret' => $client_secret
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            error_log("네이버 API 오류: " . $response->get_error_message());
            return $data;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log("네이버 API 응답 코드: " . $status_code);
        
        if ($status_code !== 200) {
            error_log("네이버 API 오류 응답: " . $body);
            return $data;
        }
        
        $result = json_decode($body, true);
        
        if (!empty($result['items'])) {
            error_log("네이버 검색 결과: " . count($result['items']) . "개");
            
            // 가장 매칭도가 높은 결과 선택
            $best_match = $this->find_best_naver_match($result['items'], $place_name);
            
            if ($best_match) {
                error_log("최적 매칭 찾음: " . $best_match['title']);
                
                // 네이버 플레이스 상세 정보 추출
                $data = $this->extract_naver_place_details($best_match, $place_type);
            } else {
                error_log("적합한 매칭 결과 없음");
            }
        } else {
            error_log("네이버 검색 결과 없음");
        }
        
        return $data;
    }
    
    /**
     * 네이버 검색 결과에서 가장 매칭도가 높은 결과 찾기 (수정된 버전)
     */
    private function find_best_naver_match($items, $target_name) {
        $best_match = null;
        $highest_score = 0;
        
        foreach ($items as $item) {
            // HTML 태그 제거
            $item_name = strip_tags($item['title']);
            
            $similarity = 0;
            similar_text($target_name, $item_name, $similarity);
            
            // 성수동 확인 (네이버 검색 API는 roadAddress 필드 사용)
            if (!empty($item['roadAddress']) && strpos($item['roadAddress'], '성수') !== false) {
                $similarity += 10;
            }
            
            // 주소 정보로도 확인
            if (!empty($item['address']) && strpos($item['address'], '성수') !== false) {
                $similarity += 10;
            }
            
            if ($similarity > $highest_score) {
                $highest_score = $similarity;
                $best_match = $item;
            }
        }
        
        return $highest_score > 60 ? $best_match : null; // 임계값을 70에서 60으로 낮춤
    }
    
    /**
     * 네이버 플레이스 상세 정보 추출 (수정된 버전)
     */
    private function extract_naver_place_details($place_data, $place_type) {
        $extracted = array();
        
        // 기본 정보
        if (!empty($place_data['category'])) {
            $extracted['cuisine_type'] = $this->clean_category($place_data['category']);
        }
        
        if (!empty($place_data['telephone'])) {
            $extracted['phone'] = $place_data['telephone'];
        }
        
        if (!empty($place_data['link'])) {
            $extracted['website'] = $place_data['link'];
        }
        
        // 주소 정보
        if (!empty($place_data['roadAddress'])) {
            $extracted['road_address'] = strip_tags($place_data['roadAddress']);
        }
        
        // description에서 추가 정보 추출 시도
        if (!empty($place_data['description'])) {
            $description = strip_tags($place_data['description']);
            
            // 영업시간 추출 시도
            $hours = $this->extract_business_hours($description);
            if ($hours) {
                $extracted['opening_hours'] = $hours;
            }
            
            // 메뉴 정보 추출 시도
            if (preg_match('/메뉴\s*[:：]\s*([^,，]+(?:[,，]\s*[^,，]+)*)/u', $description, $matches)) {
                $extracted['menu_items'] = trim($matches[1]);
            }
        }
        
        return $extracted;
    }
    
    /**
     * 카카오에서 데이터 수집
     */
    private function collect_from_kakao($place_name, $address, $place_type) {
        $data = array();
        
        $kakao_api_key = get_option('kakao_rest_api_key', '');
        if (empty($kakao_api_key)) {
            error_log("카카오 API 키 없음");
            return $data;
        }
        
        // 카카오 로컬 API - 키워드 검색
        $url = 'https://dapi.kakao.com/v2/local/search/keyword.json';
        $url .= '?query=' . urlencode($place_name . ' ' . $address);
        $url .= '&size=5';
        
        error_log("카카오 API URL: " . $url);
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'KakaoAK ' . $kakao_api_key
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            error_log("카카오 API 오류: " . $response->get_error_message());
            return $data;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log("카카오 API 응답 코드: " . $status_code);
        
        if ($status_code !== 200) {
            error_log("카카오 API 오류 응답: " . $body);
            return $data;
        }
        
        $result = json_decode($body, true);
        
        if (!empty($result['documents'])) {
            error_log("카카오 검색 결과: " . count($result['documents']) . "개");
            
            // 가장 매칭도가 높은 결과 선택
            $best_match = $this->find_best_kakao_match($result['documents'], $place_name);
            
            if ($best_match) {
                error_log("최적 매칭 찾음: " . $best_match['place_name']);
                
                // 카카오 상세 정보 추출
                $data = $this->extract_kakao_place_details($best_match, $place_type);
                
                // 추가 상세 정보가 있는 경우
                if (!empty($best_match['id'])) {
                    $detail_data = $this->get_kakao_place_detail($best_match['id'], $kakao_api_key);
                    $data = array_merge($data, $detail_data);
                }
            }
        } else {
            error_log("카카오 검색 결과 없음");
        }
        
        return $data;
    }
    
    /**
     * 카카오 장소 상세정보 조회
     */
    private function get_kakao_place_detail($place_id, $api_key) {
        $data = array();
        
        // 카카오는 별도의 상세정보 API가 없으므로 기본 정보만 반환
        return $data;
    }
    
    /**
     * 카카오 플레이스 상세 정보 추출 (개선된 버전)
     */
    private function extract_kakao_place_details($place_data, $place_type) {
        $extracted = array();
        
        // 기본 정보
        if (!empty($place_data['category_name'])) {
            $extracted['cuisine_type'] = $this->clean_category($place_data['category_name']);
        }
        
        if (!empty($place_data['phone'])) {
            $extracted['phone'] = $place_data['phone'];
        }
        
        if (!empty($place_data['place_url'])) {
            $extracted['kakao_place_url'] = $place_data['place_url'];
        }
        
        // 주소 정보
        if (!empty($place_data['road_address_name'])) {
            $extracted['road_address'] = $place_data['road_address_name'];
        }
        
        // 거리 정보 (미터 단위)
        if (!empty($place_data['distance'])) {
            $extracted['distance'] = $place_data['distance'] . 'm';
        }
        
        return $extracted;
    }
    
    /**
     * 카카오 검색 결과에서 가장 매칭도가 높은 결과 찾기
     */
    private function find_best_kakao_match($documents, $target_name) {
        $best_match = null;
        $highest_score = 0;
        
        foreach ($documents as $doc) {
            $similarity = 0;
            similar_text($target_name, $doc['place_name'], $similarity);
            
            // 성수동 확인
            if (strpos($doc['address_name'], '성수') !== false) {
                $similarity += 10;
            }
            
            if ($similarity > $highest_score) {
                $highest_score = $similarity;
                $best_match = $doc;
            }
        }
        
        return $highest_score > 60 ? $best_match : null;
    }
    
    /**
     * 구글에서 데이터 수집
     */
    private function collect_from_google($place_name, $address, $place_type) {
        $data = array();
        
        $google_api_key = get_option('google_places_api_key', '');
        if (empty($google_api_key)) {
            error_log("구글 API 키 없음");
            return $data;
        }
        
        // Google Places API - Find Place
        $search_url = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json';
        $search_url .= '?input=' . urlencode($place_name . ' ' . $address);
        $search_url .= '&inputtype=textquery';
        $search_url .= '&fields=place_id,name,formatted_address';
        $search_url .= '&language=ko';
        $search_url .= '&key=' . $google_api_key;
        
        error_log("구글 API URL: " . $search_url);
        
        $response = wp_remote_get($search_url, array('timeout' => 10));
        
        if (is_wp_error($response)) {
            error_log("구글 API 오류: " . $response->get_error_message());
            return $data;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (isset($result['status']) && $result['status'] !== 'OK') {
            error_log("구글 API 상태: " . $result['status']);
            if (isset($result['error_message'])) {
                error_log("구글 API 오류 메시지: " . $result['error_message']);
            }
            return $data;
        }
        
        if (!empty($result['candidates'][0]['place_id'])) {
            $place_id = $result['candidates'][0]['place_id'];
            
            // Place Details API로 상세 정보 가져오기
            $data = $this->get_google_place_details($place_id, $place_type, $google_api_key);
        }
        
        return $data;
    }
    
    /**
     * 구글 플레이스 상세 정보 가져오기
     */
    private function get_google_place_details($place_id, $place_type, $api_key) {
        $data = array();
        
        // 필요한 필드 정의
        $fields = array(
            'opening_hours',
            'price_level',
            'types',
            'website',
            'formatted_phone_number',
            'serves_vegetarian_food',
            'serves_alcohol',
            'takeout',
            'delivery',
            'dine_in',
            'reservable',
            'wheelchair_accessible_entrance',
            'serves_breakfast',
            'serves_lunch',
            'serves_dinner',
            'menu',
            'editorial_summary'
        );
        
        $details_url = 'https://maps.googleapis.com/maps/api/place/details/json';
        $details_url .= '?place_id=' . $place_id;
        $details_url .= '&fields=' . implode(',', $fields);
        $details_url .= '&language=ko';
        $details_url .= '&key=' . $api_key;
        
        $response = wp_remote_get($details_url, array('timeout' => 10));
        
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);
            
            if (!empty($result['result'])) {
                $data = $this->extract_google_place_details($result['result'], $place_type);
            }
        }
        
        return $data;
    }
    
    /**
     * 구글 플레이스 상세 정보 추출
     */
    private function extract_google_place_details($place_data, $place_type) {
        $extracted = array();
        
        // 영업시간
        if (!empty($place_data['opening_hours']['weekday_text'])) {
            $extracted['opening_hours'] = implode("\n", $place_data['opening_hours']['weekday_text']);
        }
        
        // 가격대
        if (isset($place_data['price_level'])) {
            $extracted['price_range'] = $this->convert_google_price_level($place_data['price_level']);
        }
        
        // 음식점 정보
        if ($place_type === 'food' || $place_type === 'restaurant') {
            if (isset($place_data['serves_vegetarian_food'])) {
                $extracted['vegetarian_options'] = $place_data['serves_vegetarian_food'];
            }
            
            if (isset($place_data['serves_alcohol'])) {
                $extracted['alcohol_served'] = $place_data['serves_alcohol'];
            }
            
            if (isset($place_data['takeout'])) {
                $extracted['takeout'] = $place_data['takeout'];
            }
            
            if (isset($place_data['delivery'])) {
                $extracted['delivery'] = $place_data['delivery'];
            }
            
            if (isset($place_data['reservable'])) {
                $extracted['reservation'] = $place_data['reservable'];
            }
        }
        
        // 웹사이트
        if (!empty($place_data['website'])) {
            $extracted['website'] = $place_data['website'];
        }
        
        // 전화번호
        if (!empty($place_data['formatted_phone_number'])) {
            $extracted['phone'] = $place_data['formatted_phone_number'];
        }
        
        return $extracted;
    }
    
    /**
     * 수집된 데이터 병합 및 정제
     */
    private function merge_and_refine_data($collected_data, $place_type) {
        $merged = array();
        $field_mappings = isset($this->field_mappings[$place_type]) ? 
                         $this->field_mappings[$place_type] : 
                         array();
        
        // 각 필드별로 가장 신뢰할 수 있는 데이터 선택
        foreach ($field_mappings as $field_name => $source_fields) {
            $field_value = null;
            
            // 우선순위: Google > Kakao > Naver
            $priority_sources = array('google', 'kakao', 'naver');
            
            foreach ($priority_sources as $source) {
                if (isset($collected_data[$source]) && 
                    isset($source_fields[$source]) && 
                    isset($collected_data[$source][$field_name])) {
                    $field_value = $collected_data[$source][$field_name];
                    break;
                }
            }
            
            if ($field_value !== null) {
                $merged[$field_name] = $field_value;
            }
        }
        
        // 공통 필드 병합
        foreach ($collected_data as $source => $data) {
            foreach ($data as $key => $value) {
                if (!isset($merged[$key]) && !empty($value)) {
                    $merged[$key] = $value;
                }
            }
        }
        
        return $merged;
    }
    
    /**
     * 기존 데이터와 병합
     */
    private function merge_with_existing_data($post_id, $new_data) {
        $merged = $new_data;
        
        // 기존 메타데이터 가져오기
        foreach ($new_data as $key => $value) {
            $existing = get_post_meta($post_id, $key, true);
            
            // 기존 데이터가 있고 비어있지 않으면 유지
            if (!empty($existing)) {
                unset($merged[$key]);
            }
        }
        
        return $merged;
    }
    
    /**
     * 수집된 데이터 저장
     */
    private function save_collected_data($post_id, $data) {
        foreach ($data as $key => $value) {
            // checkbox 타입 처리
            if (is_bool($value)) {
                $value = $value ? '1' : '';
            }
            
            // 배열 타입 처리
            if (is_array($value)) {
                $value = implode("\n", $value);
            }
            
            update_post_meta($post_id, $key, $value);
        }
        
        // 수집 이력 저장
        $collection_history = get_post_meta($post_id, '_auto_collection_history', true);
        if (!is_array($collection_history)) {
            $collection_history = array();
        }
        
        $collection_history[] = array(
            'timestamp' => current_time('mysql'),
            'fields_collected' => array_keys($data),
            'source' => 'auto_collector'
        );
        
        // 최근 10개만 유지
        if (count($collection_history) > 10) {
            $collection_history = array_slice($collection_history, -10);
        }
        
        update_post_meta($post_id, '_auto_collection_history', $collection_history);
    }
    
    /**
     * 카테고리 정리
     */
    private function clean_category($category) {
        // 불필요한 문자 제거
        $category = str_replace(array('>', '|'), ' ', $category);
        $category = trim($category);
        
        // 마지막 카테고리만 사용
        $parts = explode(' ', $category);
        return end($parts);
    }
    
    /**
     * 영업시간 추출
     */
    private function extract_business_hours($text) {
        // 영업시간 패턴 찾기
        if (preg_match('/영업시간[:\s]*(.*?)(?:브레이크타임|$)/s', $text, $matches)) {
            return trim($matches[1]);
        }
        
        if (preg_match('/\d{1,2}:\d{2}\s*[~-]\s*\d{1,2}:\d{2}/', $text, $matches)) {
            return $matches[0];
        }
        
        return null;
    }
    
    /**
     * 구글 가격 레벨 변환
     */
    private function convert_google_price_level($level) {
        $price_map = array(
            0 => 'free',
            1 => 'low',
            2 => 'medium',
            3 => 'high',
            4 => 'premium'
        );
        
        return isset($price_map[$level]) ? $price_map[$level] : 'medium';
    }
    
    /**
     * 장소의 메타필드 타입 가져오기
     */
    private function get_place_metafield_type($post_id) {
        $terms = wp_get_post_terms($post_id, 'place_type');
        
        if (!empty($terms) && !is_wp_error($terms)) {
            $term = $terms[0];
            $metafield_type = get_term_meta($term->term_id, 'metafield_type', true);
            
            // 메타필드 타입이 있으면 사용
            if (!empty($metafield_type)) {
                return $metafield_type;
            }
            
            // 없으면 slug로 판단
            if (in_array($term->slug, array('cafe', 'restaurant', 'bar'))) {
                return 'food';
            }
            
            if (in_array($term->slug, array('shop', 'retail-store', 'popup-store'))) {
                return 'shop';
            }
        }
        
        return 'general';
    }
    
    /**
     * AJAX: 장소 상세정보 수집
     */
    public function ajax_collect_place_details() {
        check_ajax_referer('collect_place_details', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('권한이 없습니다.');
        }
        
        $post_id = intval($_POST['post_id']);
        $overwrite = isset($_POST['overwrite']) && $_POST['overwrite'] === 'true';
        
        if (!$post_id) {
            wp_send_json_error('잘못된 요청입니다.');
        }
        
        // 수집 실행
        $collected_data = $this->collect_place_details($post_id, array(
            'overwrite' => $overwrite
        ));
        
        if (empty($collected_data)) {
            wp_send_json_error('수집된 정보가 없습니다.');
        }
        
        // 수집 결과 포맷팅
        $result = array(
            'message' => sprintf('%d개의 필드가 자동으로 수집되었습니다.', count($collected_data)),
            'fields' => array()
        );
        
        // 필드 레이블과 값 추가
        $place_type = $this->get_place_metafield_type($post_id);
        
        // PlaceMetaFields 클래스가 있는지 확인
        if (class_exists('PlaceMetaFields')) {
            $field_definitions = PlaceMetaFields::get_fields_for_type($place_type);
            
            foreach ($collected_data as $field_name => $value) {
                if (isset($field_definitions[$field_name])) {
                    $result['fields'][] = array(
                        'name' => $field_name,
                        'label' => $field_definitions[$field_name]['label'],
                        'value' => $value
                    );
                }
            }
        } else {
            // PlaceMetaFields 클래스가 없는 경우 기본 처리
            foreach ($collected_data as $field_name => $value) {
                $result['fields'][] = array(
                    'name' => $field_name,
                    'label' => ucfirst(str_replace('_', ' ', $field_name)),
                    'value' => $value
                );
            }
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: API 가용성 테스트
     */
    public function ajax_test_api_availability() {
        check_ajax_referer('test_api_availability', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('권한이 없습니다.');
        }
        
        $results = array(
            'naver' => $this->test_naver_api(),
            'kakao' => $this->test_kakao_api(),
            'google' => $this->test_google_api()
        );
        
        wp_send_json_success($results);
    }
    
    /**
     * 네이버 API 테스트 (수정된 버전)
     */
    private function test_naver_api() {
        $client_id = get_option('sungsuya_naver_client_id', '');
        $client_secret = get_option('sungsuya_naver_client_secret', '');
        
        if (empty($client_id) || empty($client_secret)) {
            return array('status' => 'error', 'message' => 'API 키가 설정되지 않았습니다.');
        }
        
        // 네이버 개발자 센터 검색 API 사용
        $url = 'https://openapi.naver.com/v1/search/local.json?query=' . urlencode('성수동 카페') . '&display=1';
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'X-Naver-Client-Id' => $client_id,
                'X-Naver-Client-Secret' => $client_secret
            ),
            'timeout' => 5
        ));
        
        if (is_wp_error($response)) {
            return array('status' => 'error', 'message' => $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            if (isset($data['total'])) {
                return array('status' => 'success', 'message' => 'API 정상 작동 (검색 API)');
            }
        }
        
        $body = wp_remote_retrieve_body($response);
        return array('status' => 'error', 'message' => 'HTTP ' . $status_code . ' - ' . substr($body, 0, 100));
    }
    
    /**
     * 카카오 API 테스트
     */
    private function test_kakao_api() {
        $api_key = get_option('kakao_rest_api_key', '');
        
        if (empty($api_key)) {
            return array('status' => 'error', 'message' => 'API 키가 설정되지 않았습니다.');
        }
        
        $url = 'https://dapi.kakao.com/v2/local/search/keyword.json?query=' . urlencode('성수동');
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'KakaoAK ' . $api_key
            ),
            'timeout' => 5
        ));
        
        if (is_wp_error($response)) {
            return array('status' => 'error', 'message' => $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 200) {
            return array('status' => 'success', 'message' => 'API 정상 작동');
        } else {
            return array('status' => 'error', 'message' => 'HTTP ' . $status_code);
        }
    }
    
    /**
     * 구글 API 테스트
     */
    private function test_google_api() {
        $api_key = get_option('google_places_api_key', '');
        
        if (empty($api_key)) {
            return array('status' => 'error', 'message' => 'API 키가 설정되지 않았습니다.');
        }
        
        $url = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json';
        $url .= '?input=' . urlencode('성수동');
        $url .= '&inputtype=textquery';
        $url .= '&key=' . $api_key;
        
        $response = wp_remote_get($url, array('timeout' => 5));
        
        if (is_wp_error($response)) {
            return array('status' => 'error', 'message' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['status']) && $data['status'] === 'OK') {
            return array('status' => 'success', 'message' => 'API 정상 작동');
        } else {
            $error_message = isset($data['error_message']) ? $data['error_message'] : 'Unknown error';
            return array('status' => 'error', 'message' => $error_message);
        }
    }
    
    /**
     * 메타박스에 자동수집 버튼 추가하는 스크립트
     */
    public function add_collection_button_script() {
        $screen = get_current_screen();
        
        if ($screen->post_type !== 'places' || $screen->base !== 'post') {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // 자동수집 버튼 추가
            var buttonHtml = '<button type="button" class="button button-primary" id="auto-collect-details">' +
                           '<span class="dashicons dashicons-download"></span> 상세정보 자동수집</button>';
            
            // 메타박스 제목 옆에 버튼 추가
            $('.postbox h2.hndle').each(function() {
                if ($(this).text().indexOf('메타 필드') !== -1) {
                    $(this).append(' ' + buttonHtml);
                }
            });
            
            // 자동수집 버튼 클릭 이벤트
            $('#auto-collect-details').on('click', function() {
                var $button = $(this);
                var post_id = $('#post_ID').val();
                
                if (!post_id) {
                    alert('포스트 ID를 찾을 수 없습니다.');
                    return;
                }
                
                // 덮어쓰기 확인
                var overwrite = false;
                if ($('input[name^="meta_"]:not(:empty)').length > 0) {
                    overwrite = confirm('이미 입력된 정보가 있습니다. 새로운 정보로 덮어쓰시겠습니까?');
                }
                
                $button.prop('disabled', true).text('수집 중...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'collect_place_details',
                        post_id: post_id,
                        overwrite: overwrite,
                        nonce: '<?php echo wp_create_nonce('collect_place_details'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            
                            // 수집된 필드 표시
                            if (response.data.fields.length > 0) {
                                console.log('수집된 필드:', response.data.fields);
                                
                                // 페이지 새로고침하여 수집된 데이터 표시
                                location.reload();
                            }
                        } else {
                            alert('오류: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('서버 오류가 발생했습니다.');
                    },
                    complete: function() {
                        $button.prop('disabled', false).html('<span class="dashicons dashicons-download"></span> 상세정보 자동수집');
                    }
                });
            });
        });
        </script>
        <?php
    }
}

// 싱글톤 인스턴스 생성
Place_Detail_Auto_Collector::get_instance();
