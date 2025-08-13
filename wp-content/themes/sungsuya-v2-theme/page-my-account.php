<?php
/**
 * Template Name: My Account
 * 
 * @package SungsuyaV2
 */

get_header(); 

// 로그인 체크
if (!is_user_logged_in()) {
    wp_redirect(home_url('/tour-login'));
    exit;
}

$current_user = wp_get_current_user();

// 사용자 통계 가져오기
$tour_saver = new SungsuyaTourSaver();
$tour_count = $tour_saver->get_user_tour_count($current_user->ID);

// 리뷰 개수 (현재는 0으로 표시)
$review_count = 0;
$liked_places = 0;
?>

<style>
.my-account-page {
    padding: 100px 0;
    min-height: calc(100vh - 200px);
    background: var(--dark-bg);
}

.account-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 60px;
}

.page-title {
    font-family: var(--font-display);
    font-size: 48px;
    margin-bottom: 16px;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-subtitle {
    color: var(--muted-text);
    font-size: 18px;
}

.account-grid {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 40px;
    margin-top: 60px;
}

.account-sidebar {
    background: var(--dark-surface);
    border-radius: 16px;
    padding: 30px;
}

.user-info {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid var(--dark-border);
}

.user-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: var(--primary-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    font-weight: 700;
    color: white;
    margin: 0 auto 20px;
}

.user-name {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 8px;
}

.user-email {
    color: var(--muted-text);
    font-size: 14px;
}

.account-nav {
    list-style: none;
    padding: 0;
    margin: 0;
}

.account-nav li {
    margin-bottom: 8px;
}

.account-nav a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 8px;
    color: #FFFFFF;
    opacity: 0.7;
    transition: all 0.3s ease;
}

.account-nav a:hover,
.account-nav a.active {
    background: rgba(255, 255, 255, 0.1);
    opacity: 1;
}

.account-content {
    background: var(--dark-surface);
    border-radius: 16px;
    padding: 40px;
}

.info-section {
    margin-bottom: 40px;
}

.section-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--dark-border);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.info-label {
    font-size: 14px;
    color: var(--muted-text);
}

.info-value {
    font-size: 16px;
    font-weight: 500;
}

.edit-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: var(--primary-gradient);
    color: white;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.edit-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    margin-top: 40px;
}

.stat-card {
    background: var(--dark-bg);
    border: 1px solid var(--dark-border);
    border-radius: 12px;
    padding: 24px;
    text-align: center;
}

.stat-value {
    font-size: 36px;
    font-weight: 700;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-label {
    color: var(--muted-text);
    font-size: 14px;
    margin-top: 8px;
}

@media (max-width: 768px) {
    .account-grid {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<main class="my-account-page">
    <div class="account-container">
        <div class="page-header">
            <h1 class="page-title">내 정보</h1>
            <p class="page-subtitle">프로필 정보와 설정을 관리하세요</p>
        </div>
        
        <div class="account-grid">
            <aside class="account-sidebar">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($current_user->display_name, 0, 1)); ?>
                    </div>
                    <h3 class="user-name"><?php echo esc_html($current_user->display_name); ?></h3>
                    <p class="user-email"><?php echo esc_html($current_user->user_email); ?></p>
                </div>
                
                <nav>
                    <ul class="account-nav">
                        <li>
                            <a href="<?php echo home_url('/my-account'); ?>" class="active">
                                <i data-feather="user"></i>
                                <span>내 정보</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo home_url('/my-tours'); ?>">
                                <i data-feather="map"></i>
                                <span>내 투어</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo home_url('/my-reviews'); ?>">
                                <i data-feather="star"></i>
                                <span>내 리뷰</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo wp_logout_url(home_url()); ?>">
                                <i data-feather="log-out"></i>
                                <span>로그아웃</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>
            
            <div class="account-content">
                <div class="info-section">
                    <h2 class="section-title">기본 정보</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">사용자명</span>
                            <span class="info-value"><?php echo esc_html($current_user->user_login); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">이메일</span>
                            <span class="info-value"><?php echo esc_html($current_user->user_email); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">표시 이름</span>
                            <span class="info-value"><?php echo esc_html($current_user->display_name); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">가입일</span>
                            <span class="info-value"><?php echo date('Y년 m월 d일', strtotime($current_user->user_registered)); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h2 class="section-title">활동 현황</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo number_format($tour_count); ?></div>
                            <div class="stat-label">저장된 투어</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo number_format($review_count); ?></div>
                            <div class="stat-label">작성한 리뷰</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo number_format($liked_places); ?></div>
                            <div class="stat-label">좋아요한 장소</div>
                        </div>
                    </div>
                </div>
                
                <button class="edit-button">
                    <i data-feather="edit"></i>
                    <span>프로필 수정</span>
                </button>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    feather.replace();
});
</script>

<?php get_footer(); ?>