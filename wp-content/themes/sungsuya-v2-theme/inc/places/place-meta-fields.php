<?php
/**
 * 성수야! V2 - Places Meta Fields
 * 
 * 장소별 메타 필드 구조 정의 및 관리
 * 
 * @package SungsuyaV2
 * @version 2.1.0
 */

// 직접 액세스 방지
if (!defined('ABSPATH')) {
    exit;
}

class PlaceMetaFields {
    
    /**
     * 모든 장소 유형에 공통으로 적용되는 필드
     */
    public static function get_common_fields() {
        return array(
            'address' => array(
                'type' => 'text',
                'label' => '주소',
                'placeholder' => '예: 서울시 성동구 성수일로 13',
                'required' => true,
                'description' => '정확한 주소를 입력하면 자동으로 좌표가 계산됩니다.'
            ),
            'latitude' => array(
                'type' => 'number',
                'label' => '위도',
                'readonly' => true,
                'step' => 'any',
                'description' => '주소 입력 시 자동으로 계산됩니다.'
            ),
            'longitude' => array(
                'type' => 'number',
                'label' => '경도',
                'readonly' => true,
                'step' => 'any',
                'description' => '주소 입력 시 자동으로 계산됩니다.'
            ),
            'phone' => array(
                'type' => 'tel',
                'label' => '전화번호',
                'placeholder' => '02-1234-5678'
            ),
            'website' => array(
                'type' => 'url',
                'label' => '웹사이트',
                'placeholder' => 'https://example.com'
            ),
            'instagram' => array(
                'type' => 'text',
                'label' => '인스타그램',
                'placeholder' => '@username 또는 전체 URL'
            ),
            'operating_status' => array(
                'type' => 'select',
                'label' => '운영 상태',
                'required' => true,
                'options' => array(
                    'open' => '운영중',
                    'closed' => '영업종료',
                    'coming_soon' => '오픈 예정'
                ),
                'default' => 'open'
            ),
            'nearest_subway' => array(
                'type' => 'text',
                'label' => '가장 가까운 지하철역',
                'readonly' => true,
                'description' => '주소 입력 시 자동으로 계산됩니다.'
            ),
            'subway_distance' => array(
                'type' => 'text',
                'label' => '지하철역까지 도보시간',
                'readonly' => true,
                'description' => '분 단위로 자동 계산됩니다.'
            ),
            'featured' => array(
                'type' => 'checkbox',
                'label' => '인기 장소로 표시'
            )
        );
    }
    
    /**
     * 장소 유형별 특화 필드
     */
    public static function get_type_specific_fields() {
        return array(
            'popup-store' => array(  // slug 형태로 변경
                // === 기본 정보 그룹 (8개) ===
                'store_name' => array(
                    'type' => 'text',
                    'label' => '스토어명',
                    'placeholder' => '예: 나이키 성수 팝업스토어',
                    'required' => true,
                    'group' => 'basic'
                ),
                'brand_name' => array(
                    'type' => 'text',
                    'label' => '브랜드명',
                    'placeholder' => '예: 나이키, 아디다스',
                    'required' => true,
                    'group' => 'basic'
                ),
                'store_description' => array(
                    'type' => 'textarea',
                    'label' => '스토어 설명',
                    'placeholder' => '팝업스토어의 특별한 컨셉이나 특징을 설명해주세요',
                    'rows' => 4,
                    'group' => 'basic'
                ),
                'popup_category' => array(
                    'type' => 'select',
                    'label' => '팝업 카테고리',
                    'required' => true,
                    'options' => array(
                        'fashion' => '👗 패션',
                        'beauty' => '💄 뷰티',
                        'food' => '🍽️ 푸드',
                        'lifestyle' => '🛋️ 라이프스타일',
                        'art' => '🎨 아트',
                        'tech' => '💻 테크',
                        'sports' => '⚽ 스포츠'
                    ),
                    'group' => 'basic'
                ),
                'collaboration' => array(
                    'type' => 'text',
                    'label' => '콜라보레이션',
                    'placeholder' => '협업 브랜드나 아티스트명',
                    'group' => 'basic'
                ),
                'special_options' => array(
                    'type' => 'checkbox_multiple',
                    'label' => '특별 옵션',
                    'options' => array(
                        'limited_edition' => '🎁 한정판매',
                        'collaboration' => '🤝 콜라보레이션',
                        'first_store' => '🆕 첫 매장',
                        'exclusive' => '💎 독점 상품',
                        'experience' => '🎮 체험형 스토어',
                        'photo_zone' => '📸 포토존 운영',
                        'celebrity' => '⭐ 셀럽 관련',
                        'sustainable' => '🌱 친환경'
                    ),
                    'group' => 'basic'
                ),
                'target_audience' => array(
                    'type' => 'select',
                    'label' => '타겟층',
                    'options' => array(
                        'teens' => '10대',
                        'twenties' => '20대',
                        'thirties' => '30대',
                        'forties_plus' => '40대 이상',
                        'all_ages' => '전연령',
                        'kids' => '어린이',
                        'family' => '가족'
                    ),
                    'group' => 'basic'
                ),
                'price_range' => array(
                    'type' => 'select',
                    'label' => '가격대',
                    'options' => array(
                        'budget' => '💰 저가 (1만원 이하)',
                        'moderate' => '💰💰 중간 (1-5만원)',
                        'premium' => '💰💰💰 고가 (5-10만원)',
                        'luxury' => '💰💰💰💰 럭셔리 (10만원 이상)',
                        'various' => '다양한 가격대'
                    ),
                    'group' => 'basic'
                ),
                
                // === 운영 정보 그룹 (8개) ===
                'operation_start' => array(
                    'type' => 'date',
                    'label' => '운영 시작일',
                    'required' => true,
                    'group' => 'operation'
                ),
                'operation_end' => array(
                    'type' => 'date',
                    'label' => '운영 종료일',
                    'required' => true,
                    'group' => 'operation'
                ),
                'opening_hours' => array(
                    'type' => 'textarea',
                    'label' => '운영시간',
                    'placeholder' => "평일: 11:00-21:00\n주말: 10:00-22:00\n월요일 휴무",
                    'rows' => 4,
                    'group' => 'operation'
                ),
                'operation_status' => array(
                    'type' => 'select',
                    'label' => '운영 상태',
                    'required' => true,
                    'options' => array(
                        'upcoming' => '🔜 오픈 예정',
                        'open' => '✅ 운영 중',
                        'closing_soon' => '⏰ 곧 종료',
                        'closed' => '❌ 종료됨',
                        'temporary_closed' => '⏸️ 임시 휴업'
                    ),
                    'default' => 'upcoming',
                    'group' => 'operation'
                ),
                'reservation_required' => array(
                    'type' => 'select',
                    'label' => '예약 필요 여부',
                    'options' => array(
                        'no' => '❌ 예약 불필요',
                        'recommended' => '👍 예약 권장',
                        'required' => '✅ 예약 필수'
                    ),
                    'default' => 'no',
                    'group' => 'operation'
                ),
                'entry_fee' => array(
                    'type' => 'text',
                    'label' => '입장료',
                    'placeholder' => '예: 무료, 5,000원, 음료 주문 시 무료',
                    'group' => 'operation'
                ),
                'parking_info' => array(
                    'type' => 'select',
                    'label' => '주차 정보',
                    'options' => array(
                        'available' => '🅿️ 주차 가능',
                        'limited' => '🅿️ 제한적 주차',
                        'unavailable' => '❌ 주차 불가',
                        'nearby' => '🚗 인근 주차장 이용'
                    ),
                    'group' => 'operation'
                ),
                'age_restriction' => array(
                    'type' => 'text',
                    'label' => '연령 제한',
                    'placeholder' => '예: 전연령, 19세 이상, 보호자 동반 시 가능',
                    'group' => 'operation'
                ),
                
                // === 소셜 & 연락처 그룹 (10개) ===
                'phone_number' => array(
                    'type' => 'tel',
                    'label' => '전화번호',
                    'placeholder' => '02-1234-5678',
                    'group' => 'contact'
                ),
                'email' => array(
                    'type' => 'email',
                    'label' => '이메일',
                    'placeholder' => 'contact@brand.com',
                    'group' => 'contact'
                ),
                'instagram_url' => array(
                    'type' => 'url',
                    'label' => '인스타그램',
                    'placeholder' => 'https://instagram.com/brand',
                    'group' => 'contact'
                ),
                'facebook_url' => array(
                    'type' => 'url',
                    'label' => '페이스북',
                    'placeholder' => 'https://facebook.com/brand',
                    'group' => 'contact'
                ),
                'youtube_url' => array(
                    'type' => 'url',
                    'label' => '유튜브',
                    'placeholder' => 'https://youtube.com/brand',
                    'group' => 'contact'
                ),
                'website_url' => array(
                    'type' => 'url',
                    'label' => '공식 웹사이트',
                    'placeholder' => 'https://brand.com',
                    'group' => 'contact'
                ),
                'hashtags' => array(
                    'type' => 'text',
                    'label' => '해시태그',
                    'placeholder' => '#성수동 #팝업스토어 #브랜드명',
                    'group' => 'contact'
                ),
                'booking_url' => array(
                    'type' => 'url',
                    'label' => '예약 링크',
                    'placeholder' => 'https://booking.com/...',
                    'group' => 'contact'
                ),
                'event_url' => array(
                    'type' => 'url',
                    'label' => '이벤트 참여 링크',
                    'placeholder' => 'https://event.com/...',
                    'group' => 'contact'
                ),
                'press_contact' => array(
                    'type' => 'text',
                    'label' => '보도자료 문의',
                    'placeholder' => '담당자명 또는 연락처',
                    'group' => 'contact'
                ),
                
                // === 투어 최적화 그룹 (15개) ===
                'recommended_visit_duration' => array(
                    'type' => 'select',
                    'label' => '권장 체류시간',
                    'options' => array(
                        '15-30' => '⏱️ 15-30분 (빠른 구경)',
                        '30-60' => '⏱️ 30분-1시간 (여유롭게)',
                        '60-90' => '⏱️ 1-1.5시간 (충분히)',
                        '90-120' => '⏱️ 1.5-2시간 (깊이 있게)',
                        '120+' => '⏱️ 2시간 이상 (체험형)'
                    ),
                    'group' => 'tour'
                ),
                'best_visit_time' => array(
                    'type' => 'select',
                    'label' => '권장 방문시간대',
                    'options' => array(
                        'morning' => '🌅 오전 (10:00-12:00)',
                        'lunch' => '🍽️ 점심 (12:00-14:00)',
                        'afternoon' => '☀️ 오후 (14:00-17:00)',
                        'evening' => '🌆 저녁 (17:00-20:00)',
                        'night' => '🌙 밤 (20:00 이후)',
                        'anytime' => '⏰ 언제든지'
                    ),
                    'group' => 'tour'
                ),
                'crowd_level_weekday' => array(
                    'type' => 'select',
                    'label' => '평일 혼잡도',
                    'options' => array(
                        'low' => '🟢 여유로움 (대기 없음)',
                        'medium' => '🟡 보통 (짧은 대기)',
                        'high' => '🟠 혼잡함 (대기 예상)',
                        'very_high' => '🔴 매우 혼잡 (긴 대기)'
                    ),
                    'group' => 'tour'
                ),
                'crowd_level_weekend' => array(
                    'type' => 'select',
                    'label' => '주말 혼잡도',
                    'options' => array(
                        'low' => '🟢 여유로움 (대기 없음)',
                        'medium' => '🟡 보통 (짧은 대기)',
                        'high' => '🟠 혼잡함 (대기 예상)',
                        'very_high' => '🔴 매우 혼잡 (긴 대기)'
                    ),
                    'group' => 'tour'
                ),
                'accessibility_features' => array(
                    'type' => 'checkbox_multiple',
                    'label' => '접근성 정보',
                    'options' => array(
                        'wheelchair_accessible' => '♿ 휠체어 접근 가능',
                        'elevator_available' => '🛗 엘리베이터 이용 가능',
                        'parking_available' => '🅿️ 주차 가능',
                        'stroller_friendly' => '👶 유모차 접근 가능',
                        'guide_dog_allowed' => '🐕‍🦺 안내견 출입 가능',
                        'audio_guide' => '🔊 음성 가이드 제공',
                        'sign_language' => '👋 수어 서비스',
                        'senior_friendly' => '👵 고령자 친화'
                    ),
                    'group' => 'tour'
                ),
                'photography_policy' => array(
                    'type' => 'select',
                    'label' => '사진 촬영 정책',
                    'options' => array(
                        'free' => '📸 자유 촬영',
                        'designated_areas' => '📍 지정구역만 촬영',
                        'no_flash' => '📸 플래시 금지',
                        'permission_required' => '✋ 허가 필요',
                        'prohibited' => '❌ 촬영 금지'
                    ),
                    'group' => 'tour'
                ),
                'nearby_recommendations' => array(
                    'type' => 'textarea',
                    'label' => '주변 추천 장소',
                    'placeholder' => '주변 카페, 식당, 다른 팝업스토어 등...',
                    'rows' => 3,
                    'group' => 'tour'
                ),
                'combination_tips' => array(
                    'type' => 'textarea',
                    'label' => '조합 팁',
                    'placeholder' => '이 스토어와 함께 방문하면 좋은 장소나 순서...',
                    'rows' => 3,
                    'group' => 'tour'
                ),
                'must_see_items' => array(
                    'type' => 'textarea',
                    'label' => '필수 체크 아이템',
                    'placeholder' => '꼭 봐야 할 제품이나 전시물...',
                    'rows' => 3,
                    'group' => 'tour'
                ),
                'photo_spots' => array(
                    'type' => 'textarea',
                    'label' => '포토 스팟',
                    'placeholder' => '인스타그램 사진 찍기 좋은 장소...',
                    'rows' => 3,
                    'group' => 'tour'
                ),
                'visit_tips' => array(
                    'type' => 'textarea',
                    'label' => '방문 팁',
                    'placeholder' => '방문 전 알아두면 좋은 팁들...',
                    'rows' => 3,
                    'group' => 'tour'
                ),
                'queue_info' => array(
                    'type' => 'text',
                    'label' => '대기줄 정보',
                    'placeholder' => '예: 평균 대기시간 30분, 번호표 발급',
                    'group' => 'tour'
                ),
                'seasonal_info' => array(
                    'type' => 'textarea',
                    'label' => '계절별 정보',
                    'placeholder' => '계절에 따른 특별한 정보나 주의사항...',
                    'rows' => 2,
                    'group' => 'tour'
                ),
                'weather_considerations' => array(
                    'type' => 'text',
                    'label' => '날씨 고려사항',
                    'placeholder' => '예: 우천 시 실내로 이동, 야외 전시 취소 가능',
                    'group' => 'tour'
                ),
                'group_visit_info' => array(
                    'type' => 'text',
                    'label' => '단체 방문 정보',
                    'placeholder' => '예: 10명 이상 사전 예약 필수',
                    'group' => 'tour'
                )
            ),
            // 호환성을 위한 popup_store (언더스코어 버전) - popup-store와 동일한 필드 구조
            'popup_store' => array(
                // === 기본 정보 그룹 (8개) ===
                'store_name' => array(
                    'type' => 'text',
                    'label' => '스토어명',
                    'placeholder' => '예: 나이키 성수 팝업스토어',
                    'required' => true,
                    'group' => 'basic'
                ),
                'brand_name' => array(
                    'type' => 'text',
                    'label' => '브랜드명',
                    'placeholder' => '예: 나이키, 아디다스',
                    'required' => true,
                    'group' => 'basic'
                ),
                'store_description' => array(
                    'type' => 'textarea',
                    'label' => '스토어 설명',
                    'placeholder' => '팝업스토어의 특별한 컨셉이나 특징을 설명해주세요',
                    'rows' => 4,
                    'group' => 'basic'
                ),
                'popup_category' => array(
                    'type' => 'select',
                    'label' => '팝업 카테고리',
                    'required' => true,
                    'options' => array(
                        'fashion' => '👗 패션',
                        'beauty' => '💄 뷰티',
                        'food' => '🍽️ 푸드',
                        'lifestyle' => '🛋️ 라이프스타일',
                        'art' => '🎨 아트',
                        'tech' => '💻 테크',
                        'sports' => '⚽ 스포츠'
                    ),
                    'group' => 'basic'
                ),
                'collaboration' => array(
                    'type' => 'text',
                    'label' => '콜라보레이션',
                    'placeholder' => '협업 브랜드나 아티스트명',
                    'group' => 'basic'
                ),
                'special_options' => array(
                    'type' => 'checkbox_multiple',
                    'label' => '특별 옵션',
                    'options' => array(
                        'limited_edition' => '🎁 한정판매',
                        'collaboration' => '🤝 콜라보레이션',
                        'first_store' => '🆕 첫 매장',
                        'exclusive' => '💎 독점 상품',
                        'experience' => '🎮 체험형 스토어',
                        'photo_zone' => '📸 포토존 운영',
                        'celebrity' => '⭐ 셀럽 관련',
                        'sustainable' => '🌱 친환경'
                    ),
                    'group' => 'basic'
                ),
                'target_audience' => array(
                    'type' => 'select',
                    'label' => '타겟층',
                    'options' => array(
                        'teens' => '10대',
                        'twenties' => '20대',
                        'thirties' => '30대',
                        'forties_plus' => '40대 이상',
                        'all_ages' => '전연령',
                        'kids' => '어린이',
                        'family' => '가족'
                    ),
                    'group' => 'basic'
                ),
                'price_range' => array(
                    'type' => 'select',
                    'label' => '가격대',
                    'options' => array(
                        'budget' => '💰 저가 (1만원 이하)',
                        'moderate' => '💰💰 중간 (1-5만원)',
                        'premium' => '💰💰💰 고가 (5-10만원)',
                        'luxury' => '💰💰💰💰 럭셔리 (10만원 이상)',
                        'various' => '다양한 가격대'
                    ),
                    'group' => 'basic'
                ),
                
                // === 운영 정보 그룹 (8개) ===
                'operation_start' => array(
                    'type' => 'date',
                    'label' => '운영 시작일',
                    'required' => true,
                    'group' => 'operation'
                ),
                'operation_end' => array(
                    'type' => 'date',
                    'label' => '운영 종료일',
                    'required' => true,
                    'group' => 'operation'
                ),
                'opening_hours' => array(
                    'type' => 'textarea',
                    'label' => '운영시간',
                    'placeholder' => "평일: 11:00-21:00\n주말: 10:00-22:00\n월요일 휴무",
                    'rows' => 4,
                    'group' => 'operation'
                ),
                'operation_status' => array(
                    'type' => 'select',
                    'label' => '운영 상태',
                    'required' => true,
                    'options' => array(
                        'upcoming' => '🔜 오픈 예정',
                        'open' => '✅ 운영 중',
                        'closing_soon' => '⏰ 곧 종료',
                        'closed' => '❌ 종료됨',
                        'temporary_closed' => '⏸️ 임시 휴업'
                    ),
                    'default' => 'upcoming',
                    'group' => 'operation'
                ),
                'reservation_required' => array(
                    'type' => 'select',
                    'label' => '예약 필요 여부',
                    'options' => array(
                        'no' => '❌ 예약 불필요',
                        'recommended' => '👍 예약 권장',
                        'required' => '✅ 예약 필수'
                    ),
                    'default' => 'no',
                    'group' => 'operation'
                ),
                'entry_fee' => array(
                    'type' => 'text',
                    'label' => '입장료',
                    'placeholder' => '예: 무료, 5,000원, 음료 주문 시 무료',
                    'group' => 'operation'
                ),
                'parking_info' => array(
                    'type' => 'select',
                    'label' => '주차 정보',
                    'options' => array(
                        'available' => '🅿️ 주차 가능',
                        'limited' => '🅿️ 제한적 주차',
                        'unavailable' => '❌ 주차 불가',
                        'nearby' => '🚗 인근 주차장 이용'
                    ),
                    'group' => 'operation'
                ),
                'age_restriction' => array(
                    'type' => 'text',
                    'label' => '연령 제한',
                    'placeholder' => '예: 전연령, 19세 이상, 보호자 동반 시 가능',
                    'group' => 'operation'
                ),
                
                // === 소셜 & 연락처 그룹 (10개) ===
                'phone_number' => array(
                    'type' => 'tel',
                    'label' => '전화번호',
                    'placeholder' => '02-1234-5678',
                    'group' => 'contact'
                ),
                'email' => array(
                    'type' => 'email',
                    'label' => '이메일',
                    'placeholder' => 'contact@brand.com',
                    'group' => 'contact'
                ),
                'instagram_url' => array(
                    'type' => 'url',
                    'label' => '인스타그램',
                    'placeholder' => 'https://instagram.com/brand',
                    'group' => 'contact'
                ),
                'facebook_url' => array(
                    'type' => 'url',
                    'label' => '페이스북',
                    'placeholder' => 'https://facebook.com/brand',
                    'group' => 'contact'
                ),
                'youtube_url' => array(
                    'type' => 'url',
                    'label' => '유튜브',
                    'placeholder' => 'https://youtube.com/brand',
                    'group' => 'contact'
                ),
                'website_url' => array(
                    'type' => 'url',
                    'label' => '공식 웹사이트',
                    'placeholder' => 'https://brand.com',
                    'group' => 'contact'
                ),
                'hashtags' => array(
                    'type' => 'text',
                    'label' => '해시태그',
                    'placeholder' => '#성수동 #팝업스토어 #브랜드명',
                    'group' => 'contact'
                ),
                'booking_url' => array(
                    'type' => 'url',
                    'label' => '예약 링크',
                    'placeholder' => 'https://booking.com/...',
                    'group' => 'contact'
                ),
                'event_url' => array(
                    'type' => 'url',
                    'label' => '이벤트 참여 링크',
                    'placeholder' => 'https://event.com/...',
                    'group' => 'contact'
                ),
                'press_contact' => array(
                    'type' => 'text',
                    'label' => '보도자료 문의',
                    'placeholder' => '담당자명 또는 연락처',
                    'group' => 'contact'
                ),
                
                // === 투어 최적화 그룹 (15개) ===
                'recommended_visit_duration' => array(
                    'type' => 'select',
                    'label' => '권장 체류시간',
                    'options' => array(
                        '15-30' => '⏱️ 15-30분 (빠른 구경)',
                        '30-60' => '⏱️ 30분-1시간 (여유롭게)',
                        '60-90' => '⏱️ 1-1.5시간 (충분히)',
                        '90-120' => '⏱️ 1.5-2시간 (깊이 있게)',
                        '120+' => '⏱️ 2시간 이상 (체험형)'
                    ),
                    'group' => 'tour'
                ),
                'best_visit_time' => array(
                    'type' => 'select',
                    'label' => '권장 방문시간대',
                    'options' => array(
                        'morning' => '🌅 오전 (10:00-12:00)',
                        'lunch' => '🍽️ 점심 (12:00-14:00)',
                        'afternoon' => '☀️ 오후 (14:00-17:00)',
                        'evening' => '🌆 저녁 (17:00-20:00)',
                        'night' => '🌙 밤 (20:00 이후)',
                        'anytime' => '⏰ 언제든지'
                    ),
                    'group' => 'tour'
                ),
                'crowd_level_weekday' => array(
                    'type' => 'select',
                    'label' => '평일 혼잡도',
                    'options' => array(
                        'low' => '🟢 여유로움 (대기 없음)',
                        'medium' => '🟡 보통 (짧은 대기)',
                        'high' => '🟠 혼잡함 (대기 예상)',
                        'very_high' => '🔴 매우 혼잡 (긴 대기)'
                    ),
                    'group' => 'tour'
                ),
                'crowd_level_weekend' => array(
                    'type' => 'select',
                    'label' => '주말 혼잡도',
                    'options' => array(
                        'low' => '🟢 여유로움 (대기 없음)',
                        'medium' => '🟡 보통 (짧은 대기)',
                        'high' => '🟠 혼잡함 (대기 예상)',
                        'very_high' => '🔴 매우 혼잡 (긴 대기)'
                    ),
                    'group' => 'tour'
                ),
                'accessibility_features' => array(
                    'type' => 'checkbox_multiple',
                    'label' => '접근성 정보',
                    'options' => array(
                        'wheelchair_accessible' => '♿ 휠체어 접근 가능',
                        'elevator_available' => '🛗 엘리베이터 이용 가능',
                        'parking_available' => '🅿️ 주차 가능',
                        'stroller_friendly' => '👶 유모차 접근 가능',
                        'guide_dog_allowed' => '🐕‍🦺 안내견 출입 가능',
                        'audio_guide' => '🔊 음성 가이드 제공',
                        'sign_language' => '👋 수어 서비스',
                        'senior_friendly' => '👵 고령자 친화'
                    ),
                    'group' => 'tour'
                ),
                'photography_policy' => array(
                    'type' => 'select',
                    'label' => '사진 촬영 정책',
                    'options' => array(
                        'free' => '📸 자유 촬영',
                        'designated_areas' => '📍 지정구역만 촬영',
                        'no_flash' => '📸 플래시 금지',
                        'permission_required' => '✋ 허가 필요',
                        'prohibited' => '❌ 촬영 금지'
                    ),
                    'group' => 'tour'
                ),
                'nearby_recommendations' => array(
                    'type' => 'textarea',
                    'label' => '주변 추천 장소',
                    'placeholder' => '주변 카페, 식당, 다른 팝업스토어 등...',
                    'rows' => 3,
                    'group' => 'tour'
                ),
                'combination_tips' => array(
                    'type' => 'textarea',
                    'label' => '조합 팁',
                    'placeholder' => '이 스토어와 함께 방문하면 좋은 장소나 순서...',
                    'rows' => 3,
                    'group' => 'tour'
                ),
                'must_see_items' => array(
                    'type' => 'textarea',
                    'label' => '필수 체크 아이템',
                    'placeholder' => '꼭 봐야 할 제품이나 전시물...',
                    'rows' => 3,
                    'group' => 'tour'
                ),
                'photo_spots' => array(
                    'type' => 'textarea',
                    'label' => '포토 스팟',
                    'placeholder' => '인스타그램 사진 찍기 좋은 장소...',
                    'rows' => 3,
                    'group' => 'tour'
                ),
                'visit_tips' => array(
                    'type' => 'textarea',
                    'label' => '방문 팁',
                    'placeholder' => '방문 전 알아두면 좋은 팁들...',
                    'rows' => 3,
                    'group' => 'tour'
                ),
                'queue_info' => array(
                    'type' => 'text',
                    'label' => '대기줄 정보',
                    'placeholder' => '예: 평균 대기시간 30분, 번호표 발급',
                    'group' => 'tour'
                ),
                'seasonal_info' => array(
                    'type' => 'textarea',
                    'label' => '계절별 정보',
                    'placeholder' => '계절에 따른 특별한 정보나 주의사항...',
                    'rows' => 2,
                    'group' => 'tour'
                ),
                'weather_considerations' => array(
                    'type' => 'text',
                    'label' => '날씨 고려사항',
                    'placeholder' => '예: 우천 시 실내로 이동, 야외 전시 취소 가능',
                    'group' => 'tour'
                ),
                'group_visit_info' => array(
                    'type' => 'text',
                    'label' => '단체 방문 정보',
                    'placeholder' => '예: 10명 이상 사전 예약 필수',
                    'group' => 'tour'
                )
            ),
            // 동적 장소유형을 위한 메타필드 타입별 필드 추가
            'food' => array(  // 음식점 타입 (맛집, 카페, 바 등)
                'menu_items' => array(
                    'type' => 'textarea',
                    'label' => '대표메뉴',
                    'placeholder' => '예: 아메리카노 5,000원, 카페라떼 5,500원',
                    'rows' => 4
                ),
                'price_range' => array(
                    'type' => 'select',
                    'label' => '가격대',
                    'options' => array(
                        'low' => '저렴 (1만원 이하)',
                        'medium' => '보통 (1-3만원)',
                        'high' => '비싼 (3-5만원)',
                        'premium' => '매우비싼 (5만원 이상)'
                    )
                ),
                'cuisine_type' => array(
                    'type' => 'text',
                    'label' => '음식종류',
                    'placeholder' => '예: 한식, 양식, 일식, 카페'
                ),
                'opening_hours' => array(
                    'type' => 'textarea',
                    'label' => '영업시간',
                    'placeholder' => "평일: 11:00-22:00\n주말: 10:00-23:00\n월요일 휴무",
                    'rows' => 4
                ),
                'break_time' => array(
                    'type' => 'text',
                    'label' => '브레이크타임',
                    'placeholder' => '예: 15:00-17:00'
                ),
                'last_order' => array(
                    'type' => 'text',
                    'label' => '라스트오더',
                    'placeholder' => '예: 21:30'
                ),
                'reservation' => array(
                    'type' => 'checkbox',
                    'label' => '예약 가능'
                ),
                'delivery' => array(
                    'type' => 'checkbox',
                    'label' => '배달 가능'
                ),
                'takeout' => array(
                    'type' => 'checkbox',
                    'label' => '테이크아웃 가능'
                )
            ),
            'shop' => array(  // 매장 타입 (편집샵, 상설매장, 편의시설 등)
                'brands' => array(
                    'type' => 'textarea',
                    'label' => '취급브랜드',
                    'placeholder' => '예: 나이키, 아디다스, 컨버스',
                    'rows' => 3
                ),
                'product_category' => array(
                    'type' => 'text',
                    'label' => '상품종류',
                    'placeholder' => '예: 의류, 액세서리, 생활용품'
                ),
                'price_level' => array(
                    'type' => 'select',
                    'label' => '가격대',
                    'options' => array(
                        '1-3' => '1-3만원대',
                        '3-5' => '3-5만원대',
                        '5-10' => '5-10만원대',
                        '10+' => '10만원 이상'
                    )
                ),
                'opening_hours' => array(
                    'type' => 'textarea',
                    'label' => '영업시간',
                    'placeholder' => "평일: 11:00-21:00\n주말: 10:00-22:00\n월요일 휴무",
                    'rows' => 4
                ),
                'special_services' => array(
                    'type' => 'textarea',
                    'label' => '특별서비스',
                    'placeholder' => '예: 무료 배송, 교환/환불, 맞춤 제작',
                    'rows' => 2
                ),
                'payment_methods' => array(
                    'type' => 'checkbox_multiple',
                    'label' => '결제방법',
                    'options' => array(
                        'cash' => '현금',
                        'card' => '카드',
                        'transfer' => '계좌이체',
                        'easy_pay' => '간편결제 (네이버페이, 카카오페이 등)'
                    )
                ),
                'online_shop' => array(
                    'type' => 'url',
                    'label' => '온라인샵 URL',
                    'placeholder' => 'https://example.com'
                ),
                'parking' => array(
                    'type' => 'checkbox',
                    'label' => '주차 가능'
                )
            ),
            // 기존 restaurant 필드는 호환성을 위해 유지
            'restaurant' => array(
                'cuisine_type' => array(
                    'type' => 'select',
                    'label' => '음식 종류',
                    'options' => array(
                        'korean' => '한식',
                        'japanese' => '일식',
                        'chinese' => '중식',
                        'western' => '양식',
                        'italian' => '이탈리안',
                        'thai' => '태국식',
                        'vietnamese' => '베트남식',
                        'mexican' => '멕시칸',
                        'indian' => '인도식',
                        'cafe' => '카페',
                        'bakery' => '베이커리',
                        'bar' => '바/주점',
                        'fusion' => '퓨전'
                    )
                ),
                'price_range' => array(
                    'type' => 'select',
                    'label' => '가격대',
                    'options' => array(
                        'budget' => '1만원 이하',
                        'moderate' => '1-3만원',
                        'expensive' => '3-5만원',
                        'luxury' => '5만원 이상'
                    )
                ),
                'opening_hours' => array(
                    'type' => 'textarea',
                    'label' => '영업시간',
                    'placeholder' => "평일: 11:00-22:00\n주말: 10:00-23:00\n월요일 휴무",
                    'rows' => 4
                ),
                'speciality' => array(
                    'type' => 'text',
                    'label' => '대표 메뉴/특징',
                    'placeholder' => '예: 수제 파스타, 시그니처 커피'
                ),
                'reservation' => array(
                    'type' => 'checkbox',
                    'label' => '예약 가능'
                ),
                'delivery' => array(
                    'type' => 'checkbox',
                    'label' => '배달 가능'
                )
            ),
            'retail_store' => array(
                'store_type' => array(
                    'type' => 'select',
                    'label' => '매장 유형',
                    'options' => array(
                        'fashion' => '패션',
                        'accessories' => '액세서리',
                        'shoes' => '신발',
                        'cosmetics' => '화장품',
                        'home_living' => '홈&리빙',
                        'bookstore' => '서점',
                        'electronics' => '전자제품',
                        'sporting_goods' => '스포츠용품',
                        'toys' => '장난감',
                        'music' => '음반/악기',
                        'art_craft' => '예술/공예',
                        'vintage' => '빈티지/중고'
                    )
                ),
                'brand_names' => array(
                    'type' => 'textarea',
                    'label' => '취급 브랜드',
                    'placeholder' => "브랜드명을 줄바꿈으로 구분\n예:\n나이키\n아디다스\n컨버스",
                    'rows' => 4
                ),
                'opening_hours' => array(
                    'type' => 'textarea',
                    'label' => '영업시간',
                    'placeholder' => "평일: 11:00-21:00\n주말: 10:00-22:00\n월요일 휴무",
                    'rows' => 4
                ),
                'parking' => array(
                    'type' => 'checkbox',
                    'label' => '주차 가능'
                ),
                'online_store' => array(
                    'type' => 'url',
                    'label' => '온라인 스토어',
                    'placeholder' => 'https://온라인쇼핑몰.com'
                )
            ),
            'facility' => array(
                'facility_type' => array(
                    'type' => 'select',
                    'label' => '시설 유형',
                    'required' => true,
                    'options' => array(
                        'restroom' => '화장실',
                        'parking' => '주차장',
                        'atm' => 'ATM',
                        'wifi' => '무료 WiFi',
                        'charging' => '충전소',
                        'lounge' => '휴게실',
                        'info_center' => '안내센터',
                        'first_aid' => '응급처치실'
                    )
                ),
                'accessibility' => array(
                    'type' => 'checkbox',
                    'label' => '휠체어 접근 가능'
                ),
                'free_usage' => array(
                    'type' => 'checkbox',
                    'label' => '무료 이용'
                ),
                'operating_hours' => array(
                    'type' => 'textarea',
                    'label' => '이용 시간',
                    'placeholder' => "24시간 이용 가능\n또는 구체적인 시간 입력",
                    'rows' => 3
                ),
                'capacity' => array(
                    'type' => 'number',
                    'label' => '수용 인원/대수',
                    'placeholder' => '주차장의 경우 주차 가능 대수 등'
                ),
                'additional_info' => array(
                    'type' => 'textarea',
                    'label' => '추가 정보',
                    'placeholder' => '이용 방법, 주의사항 등',
                    'rows' => 3
                )
            )
        );
    }
    
    /**
     * 필드 유효성 검사 규칙
     */
    public static function get_validation_rules() {
        return array(
            'email' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
            'phone' => '/^[0-9\-\+\(\)\s]+$/',
            'url' => '/^https?:\/\/.+/',
            'instagram' => '/^(@[a-zA-Z0-9._]{1,30}|https?:\/\/(www\.)?instagram\.com\/.+)$/'
        );
    }
    
    /**
     * 특정 유형의 필드 가져오기
     */
    public static function get_fields_for_type($type) {
        $common_fields = self::get_common_fields();
        $type_fields = self::get_type_specific_fields();
        
        if (isset($type_fields[$type])) {
            return array_merge($common_fields, $type_fields[$type]);
        }
        
        return $common_fields;
    }
    
    /**
     * 필수 필드 확인
     */
    public static function get_required_fields($type) {
        $fields = self::get_fields_for_type($type);
        $required = array();
        
        foreach ($fields as $key => $field) {
            if (isset($field['required']) && $field['required']) {
                $required[] = $key;
            }
        }
        
        return $required;
    }
    
    /**
     * 메타 데이터 저장 전 검증
     */
    public static function validate_meta_data($data, $type) {
        $validation_rules = self::get_validation_rules();
        $errors = array();
        
        foreach ($data as $key => $value) {
            if (empty($value)) continue;
            
            // 이메일 검증
            if (strpos($key, 'email') !== false && !preg_match($validation_rules['email'], $value)) {
                $errors[] = "{$key}: 올바른 이메일 형식이 아닙니다.";
            }
            
            // 전화번호 검증
            if ($key === 'phone' && !preg_match($validation_rules['phone'], $value)) {
                $errors[] = "전화번호: 올바른 형식이 아닙니다.";
            }
            
            // URL 검증
            if (($key === 'website' || $key === 'online_store') && !preg_match($validation_rules['url'], $value)) {
                $errors[] = "{$key}: 올바른 URL 형식이 아닙니다 (http:// 또는 https://로 시작).";
            }
            
            // 인스타그램 검증
            if ($key === 'instagram' && !preg_match($validation_rules['instagram'], $value)) {
                $errors[] = "인스타그램: @username 또는 인스타그램 URL 형식으로 입력해주세요.";
            }
        }
        
        return $errors;
    }
    
    /**
     * 기본값 설정
     */
    public static function get_default_values($type) {
        $defaults = array();
        $fields = self::get_fields_for_type($type);
        
        foreach ($fields as $key => $field) {
            if (isset($field['default'])) {
                $defaults[$key] = $field['default'];
            }
        }
        
        return $defaults;
    }
    
    /**
     * 장소 유형별 레이블 가져오기
     */
    public static function get_type_label($type) {
        $labels = array(
            'popup_store' => '🎪 팝업스토어',
            'restaurant' => '🍽️ 맛집',
            'retail_store' => '🏪 상설매장',
            'facility' => '🏢 편의시설'
        );
        
        return isset($labels[$type]) ? $labels[$type] : '기타';
    }
    
    /**
     * 팝업스토어 필드 그룹 정의
     */
    public static function get_popup_store_field_groups() {
        return array(
            'basic' => array(
                'title' => '기본 정보',
                'icon' => '🏪',
                'description' => '스토어의 기본적인 정보를 입력해주세요',
                'fields' => array('store_name', 'brand_name', 'store_description', 'popup_category', 'collaboration', 'special_options', 'target_audience', 'price_range')
            ),
            'operation' => array(
                'title' => '운영 정보',
                'icon' => '⏰',
                'description' => '운영 기간, 시간, 예약 등의 정보를 입력해주세요',
                'fields' => array('operation_start', 'operation_end', 'opening_hours', 'operation_status', 'reservation_required', 'entry_fee', 'parking_info', 'age_restriction')
            ),
            'contact' => array(
                'title' => '소셜 & 연락처',
                'icon' => '📱',
                'description' => '연락처와 소셜 미디어 정보를 입력해주세요',
                'fields' => array('phone_number', 'email', 'instagram_url', 'facebook_url', 'youtube_url', 'website_url', 'hashtags', 'booking_url', 'event_url', 'press_contact')
            ),
            'tour' => array(
                'title' => '투어 최적화',
                'icon' => '🗺️',
                'description' => '방문객들의 투어 계획에 도움이 되는 정보를 입력해주세요',
                'fields' => array('recommended_visit_duration', 'best_visit_time', 'crowd_level_weekday', 'crowd_level_weekend', 'accessibility_features', 'photography_policy', 'nearby_recommendations', 'combination_tips', 'must_see_items', 'photo_spots', 'visit_tips', 'queue_info', 'seasonal_info', 'weather_considerations', 'group_visit_info')
            )
        );
    }
    
    /**
     * 특정 그룹의 필드만 가져오기
     */
    public static function get_fields_by_group($type, $group) {
        // 'popup_store'와 'popup-store' 모두 지원
        if ($type !== 'popup_store' && $type !== 'popup-store') {
            return array();
        }
        
        $field_groups = self::get_popup_store_field_groups();
        if (!isset($field_groups[$group])) {
            return array();
        }
        
        // 'popup-store' 타입의 필드 가져오기
        $all_fields = self::get_fields_for_type('popup-store');
        $group_fields = array();
        
        foreach ($field_groups[$group]['fields'] as $field_name) {
            if (isset($all_fields[$field_name])) {
                $group_fields[$field_name] = $all_fields[$field_name];
            }
        }
        
        return $group_fields;
    }
}

/**
 * 메타 필드 헬퍼 함수들
 */

// 특정 장소의 메타 데이터 가져오기 (타입별 구조화) - 호환성 개선 버전
function sungsuya_get_place_meta($post_id) {
    $place_types = wp_get_post_terms($post_id, 'place_type');
    $type = !empty($place_types) ? $place_types[0]->slug : 'popup_store';
    
    $fields = PlaceMetaFields::get_fields_for_type($type);
    $meta_data = array(
        'type' => $type,
        'common' => array(),
        'specific' => array()
    );
    
    $common_fields = PlaceMetaFields::get_common_fields();
    $specific_fields = PlaceMetaFields::get_type_specific_fields();
    
    foreach ($fields as $key => $field) {
        // 호환성 개선: 언더스코어 없는 버전 우선, 있는 버전 대체
        $value = get_post_meta($post_id, $key, true);
        
        // 값이 비어있고 언더스코어가 없는 메타키라면, 언더스코어 있는 버전도 확인
        if (empty($value) && strpos($key, '_') !== 0) {
            $alt_key = '_' . $key;
            $alt_value = get_post_meta($post_id, $alt_key, true);
            if (!empty($alt_value)) {
                $value = $alt_value;
            }
        }
        
        // 반대로 언더스코어가 있는 메타키라면, 언더스코어 없는 버전도 확인
        if (empty($value) && strpos($key, '_') === 0) {
            $alt_key = substr($key, 1);
            $alt_value = get_post_meta($post_id, $alt_key, true);
            if (!empty($alt_value)) {
                $value = $alt_value;
            }
        }
        
        if (isset($common_fields[$key])) {
            $meta_data['common'][$key] = $value;
        } elseif (isset($specific_fields[$type][$key])) {
            $meta_data['specific'][$key] = $value;
        }
    }
    
    return $meta_data;
}

// 메타 데이터 저장
function sungsuya_save_place_meta($post_id, $meta_data, $type) {
    // 유효성 검사
    $errors = PlaceMetaFields::validate_meta_data($meta_data, $type);
    if (!empty($errors)) {
        return new WP_Error('validation_failed', implode('<br>', $errors));
    }
    
    // 메타 데이터 저장
    foreach ($meta_data as $key => $value) {
        if ($value !== '') {
            update_post_meta($post_id, $key, sanitize_text_field($value));
        } else {
            delete_post_meta($post_id, $key);
        }
    }
    
    return true;
}

// 특정 필드의 표시용 값 가져오기
function sungsuya_get_formatted_meta_value($post_id, $field_key, $field_config) {
    $value = get_post_meta($post_id, $field_key, true);
    
    if (empty($value)) {
        return '';
    }
    
    // 타입별 포맷팅
    switch ($field_config['type']) {
        case 'select':
            return isset($field_config['options'][$value]) ? $field_config['options'][$value] : $value;
            
        case 'checkbox':
            return $value === '1' ? '예' : '아니오';
            
        case 'checkbox_multiple':
            if (is_array($value)) {
                $labels = array();
                foreach ($value as $option_value) {
                    if (isset($field_config['options'][$option_value])) {
                        $labels[] = $field_config['options'][$option_value];
                    }
                }
                return implode(', ', $labels);
            }
            return '';
            
        case 'url':
            return sprintf('<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url($value), esc_html($value));
            
        case 'tel':
            return sprintf('<a href="tel:%s">%s</a>', preg_replace('/[^0-9+]/', '', $value), esc_html($value));
            
        case 'textarea':
            return nl2br(esc_html($value));
            
        default:
            return esc_html($value);
    }
}
