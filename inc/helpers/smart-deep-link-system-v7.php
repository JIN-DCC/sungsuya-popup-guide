<?php
/**
 * Smart Deep Link System v7.0 - 최종 확정 버전
 * 
 * 주요 변경사항:
 * - Place ID 방식 → 검색 방식으로 전환 (안정성 확보)
 * - 망고플레이트 → 블루리본 대체
 * - 배달서비스 완전 제거
 * - 검증된 플랫폼만 유지
 */

class SmartDeepLinkV7 {
    
    /**
     * 플랫폼 설정 (최종 확정 버전)
     */
    private $platforms = [
        'naver' => [
            'name' => '네이버 지도',
            'icon' => '🗺️',
            'color' => '#03C75A',
            'type' => 'search',
            'url_template' => 'https://map.naver.com/p/search/{search_query}',
            'description' => '정확한 위치와 상세 정보'
        ],
        'kakao' => [
            'name' => '카카오맵',
            'icon' => '📍',
            'color' => '#FEE500',
            'type' => 'search',
            'url_template' => 'https://map.kakao.com/?q={search_query}',
            'description' => '길찾기와 주변 정보'
        ],
        'blueribbon' => [
            'name' => '블루리본',
            'icon' => '🎖️',
            'color' => '#1E88E5',
            'type' => 'search',
            'url_template' => 'https://www.bluer.co.kr/search?query={search_query}',
            'description' => '맛집 정보와 리뷰'
        ],
        'diningcode' => [
            'name' => '다이닝코드',
            'icon' => '🍽️',
            'color' => '#FF6B35',
            'type' => 'direct',
            'url_template' => 'https://www.diningcode.com/profile.php?rid={place_id}',
            'description' => '상세 메뉴와 예약 정보'
        ],
        'instagram' => [
            'name' => '인스타그램',
            'icon' => '📸',
            'color' => '#E4405F',
            'type' => 'hashtag',
            'url_template' => 'https://www.instagram.com/explore/tags/{hashtag}/',
            'description' => '실제 방문 사진과 후기'
        ]
    ];

    /**
     * 장소별 링크 생성
     */
    public function generateLinks($placeName, $placeData = []) {
        $links = [];
        
        foreach ($this->platforms as $key => $platform) {
            $url = $this->buildUrl($key, $placeName, $placeData);
            
            if ($url) {
                $links[$key] = [
                    'name' => $platform['name'],
                    'icon' => $platform['icon'],
                    'color' => $platform['color'],
                    'url' => $url,
                    'description' => $platform['description'],
                    'type' => $platform['type']
                ];
            }
        }
        
        return $links;
    }

    /**
     * URL 생성 로직
     */
    private function buildUrl($platformKey, $placeName, $placeData) {
        $platform = $this->platforms[$platformKey];
        
        switch ($platform['type']) {
            case 'search':
                // 검색 방식: 장소명으로 검색
                $searchQuery = urlencode($placeName);
                return str_replace('{search_query}', $searchQuery, $platform['url_template']);
                
            case 'direct':
                // Direct 방식: Place ID 사용 (다이닝코드만)
                if (isset($placeData['diningcode_id'])) {
                    return str_replace('{place_id}', $placeData['diningcode_id'], $platform['url_template']);
                }
                break;
                
            case 'hashtag':
                // 해시태그 방식: 인스타그램
                $hashtag = $this->createHashtag($placeName);
                return str_replace('{hashtag}', $hashtag, $platform['url_template']);
        }
        
        return null;
    }

    /**
     * 인스타그램 해시태그 생성
     */
    private function createHashtag($placeName) {
        // 공백 제거 및 특수문자 정리
        $hashtag = preg_replace('/[^가-힣a-zA-Z0-9]/', '', $placeName);
        return $hashtag;
    }

    /**
     * WordPress용 카드 렌더링
     */
    public function renderWordPressCards($placeName, $placeData = []) {
        $links = $this->generateLinks($placeName, $placeData);
        
        $html = '<div class="smart-deep-link-v7-wp grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">';
        
        foreach ($links as $key => $link) {
            $html .= $this->renderWordPressCard($link);
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * WordPress 스타일 개별 카드
     */
    private function renderWordPressCard($link) {
        $cardColor = $this->getCardColorClass($link['color']);
        
        return "
        <div class='bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-xl transition-all transform hover:scale-105 cursor-pointer'>
            <div class='h-24 {$cardColor} flex items-center justify-center relative'>
                <span class='text-3xl text-white opacity-90'>{$link['icon']}</span>
                <div class='absolute top-2 left-2'>
                    <div class='bg-white bg-opacity-20 text-white text-xs px-2 py-1 rounded-full'>
                        v7.0
                    </div>
                </div>
            </div>
            <div class='p-4'>
                <h3 class='font-bold text-lg text-gray-800 mb-2'>{$link['name']}</h3>
                <p class='text-sm text-gray-600 mb-4'>{$link['description']}</p>
                <a href='{$link['url']}' target='_blank' rel='noopener noreferrer' 
                   class='w-full inline-block text-center py-2 px-4 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors'>
                    실제 정보 보기 →
                </a>
            </div>
        </div>";
    }

    /**
     * 색상에 따른 CSS 클래스 반환
     */
    private function getCardColorClass($color) {
        switch ($color) {
            case '#03C75A': return 'bg-gradient-to-br from-green-400 to-green-600';
            case '#FEE500': return 'bg-gradient-to-br from-yellow-400 to-orange-500';
            case '#1E88E5': return 'bg-gradient-to-br from-blue-400 to-blue-600';
            case '#FF6B35': return 'bg-gradient-to-br from-orange-400 to-red-500';
            case '#E4405F': return 'bg-gradient-to-br from-pink-400 to-purple-500';
            default: return 'bg-gradient-to-br from-gray-400 to-gray-600';
        }
    }

    /**
     * HTML 카드 생성 (기존 방식 유지)
     */
    public function renderCards($placeName, $placeData = []) {
        $links = $this->generateLinks($placeName, $placeData);
        
        $html = '<div class="smart-deep-link-v7">';
        $html .= '<h3 class="section-title">🔗 다른 플랫폼에서 더 보기</h3>';
        $html .= '<div class="platform-grid">';
        
        foreach ($links as $key => $link) {
            $html .= $this->renderCard($link);
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * 개별 카드 렌더링 (기존 방식)
     */
    private function renderCard($link) {
        return "
        <div class='platform-card' style='border-left: 4px solid {$link['color']}'>
            <div class='platform-header'>
                <span class='platform-icon'>{$link['icon']}</span>
                <span class='platform-name'>{$link['name']}</span>
            </div>
            <p class='platform-description'>{$link['description']}</p>
            <a href='{$link['url']}' target='_blank' class='platform-link' 
               style='background-color: {$link['color']}'>
                {$link['name']}에서 보기 →
            </a>
        </div>";
    }

    /**
     * CSS 스타일
     */
    public function getCSS() {
        return "
        <style>
        .smart-deep-link-v7 {
            margin: 30px 0;
            padding: 25px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .section-title {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 24px;
            font-weight: 600;
        }
        
        .platform-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .platform-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 4px solid #3498db;
        }
        
        .platform-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .platform-header {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .platform-icon {
            font-size: 24px;
            margin-right: 12px;
        }
        
        .platform-name {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .platform-description {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .platform-link {
            display: inline-block;
            padding: 12px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            text-align: center;
            box-sizing: border-box;
        }
        
        .platform-link:hover {
            opacity: 0.9;
            transform: scale(1.02);
        }
        
        @media (max-width: 768px) {
            .platform-grid {
                grid-template-columns: 1fr;
            }
            
            .smart-deep-link-v7 {
                margin: 20px 0;
                padding: 20px;
            }
        }
        </style>";
    }
}
?>