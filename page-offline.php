<?php
/**
 * Template Name: 오프라인 페이지
 * 
 * PWA 오프라인 상태에서 표시되는 페이지
 * 
 * @package SungsuyaV2
 */

// 간단한 헤더만 출력
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>오프라인 - <?php bloginfo('name'); ?></title>
    <style>
        :root {
            --primary-color: #667eea;
            --text-color: #333;
            --text-light: #666;
            --bg-color: #f7f7f7;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--bg-color);
            color: var(--text-color);
            text-align: center;
            padding: 20px;
        }
        
        .offline-container {
            max-width: 400px;
            width: 100%;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .offline-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
        
        h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 16px;
        }
        
        p {
            font-size: 16px;
            line-height: 1.6;
            color: var(--text-light);
            margin-bottom: 32px;
        }
        
        .offline-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        button {
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-secondary:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .saved-tours {
            margin-top: 40px;
            padding-top: 40px;
            border-top: 1px solid #eee;
        }
        
        .saved-tours h2 {
            font-size: 18px;
            margin-bottom: 16px;
        }
        
        .tour-list {
            text-align: left;
        }
        
        .tour-item {
            padding: 12px;
            background: var(--bg-color);
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .tour-item:hover {
            background: #e0e7ff;
        }
        
        .tour-name {
            font-weight: 500;
            color: var(--text-color);
        }
        
        .tour-meta {
            font-size: 14px;
            color: var(--text-light);
            margin-top: 4px;
        }
        
        @media (max-width: 480px) {
            .offline-container {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon">📡</div>
        
        <h1>오프라인 상태입니다</h1>
        
        <p>
            인터넷 연결이 끊어진 것 같아요.<br>
            연결이 복구되면 자동으로 새로고침됩니다.
        </p>
        
        <div class="offline-actions">
            <button class="btn-primary" onclick="checkConnection()">
                연결 상태 확인
            </button>
            <button class="btn-secondary" onclick="location.reload()">
                페이지 새로고침
            </button>
        </div>
        
        <div class="saved-tours" id="saved-tours">
            <h2>💾 저장된 투어</h2>
            <div class="tour-list" id="tour-list">
                <!-- 저장된 투어 목록이 여기에 표시됩니다 -->
            </div>
        </div>
    </div>
    
    <script>
        // 온라인 상태 감지
        window.addEventListener('online', () => {
            setTimeout(() => {
                location.reload();
            }, 1000);
        });
        
        // 연결 상태 확인
        function checkConnection() {
            if (navigator.onLine) {
                alert('인터넷에 연결되어 있습니다. 페이지를 새로고침합니다.');
                location.reload();
            } else {
                alert('아직 오프라인 상태입니다.');
            }
        }
        
        // 저장된 투어 표시
        function displaySavedTours() {
            const savedTours = JSON.parse(localStorage.getItem('savedTours') || '[]');
            const tourList = document.getElementById('tour-list');
            
            if (savedTours.length === 0) {
                tourList.innerHTML = '<p style="text-align: center; color: #999;">저장된 투어가 없습니다</p>';
                return;
            }
            
            tourList.innerHTML = savedTours.map(tour => `
                <div class="tour-item" onclick="viewTour('${tour.id}')">
                    <div class="tour-name">${tour.name}</div>
                    <div class="tour-meta">
                        ${tour.places.length}개 장소 · ${new Date(tour.savedAt).toLocaleDateString()}
                    </div>
                </div>
            `).join('');
        }
        
        // 투어 보기 (오프라인에서는 제한적)
        function viewTour(tourId) {
            alert('오프라인 상태에서는 투어를 수정할 수 없습니다.\n인터넷에 연결한 후 다시 시도해주세요.');
        }
        
        // 페이지 로드 시 저장된 투어 표시
        displaySavedTours();
        
        // 주기적으로 연결 상태 확인 (10초마다)
        setInterval(() => {
            if (navigator.onLine) {
                location.reload();
            }
        }, 10000);
    </script>
</body>
</html>
