/**
 * 성수야 PWA 인증 시스템 - 최적화된 프론트엔드 컴포넌트
 * 
 * @version 3.0.0 - 모바일 성능 최적화
 */

// 중복 선언 방지 - window.sungsuyaAuth 체크
(function() {
    // 이미 인스턴스가 있으면 종료
    if (typeof window.sungsuyaAuth !== 'undefined') {
        console.warn('SungsuyaAuth already initialized, skipping');
        return;
    }

    // JWT 디코드 헬퍼 함수
    function jwt_decode(token) {
        try {
            const base64Url = token.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
            return JSON.parse(jsonPayload);
        } catch (e) {
            return null;
        }
    }

    class SungsuyaAuth {
        constructor() {
            this.apiBase = '/wp-json/sungsuya/v1/auth';
            this.token = localStorage.getItem('sungsuya_auth_token');
            this.user = null;
            this.isGuest = false;
            this.modalCreated = false;
            this.isLoginMode = false; // 로그인/회원가입 모드 구분
            
            // 토큰이 있으면 상태 확인
            if (this.token) {
                this.checkAuthStatus();
            }
        }
        
        /**
         * 게스트 세션 시작
         */
        async startGuestSession() {
            try {
                const response = await fetch(`${this.apiBase}/guest`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    this.token = data.token;
                    this.isGuest = true;
                    localStorage.setItem('sungsuya_auth_token', data.token);
                    if (data.guest_id) {
                        localStorage.setItem('sungsuya_guest_id', data.guest_id);
                    }
                    return true;
                }
                return false;
            } catch (error) {
                console.error('게스트 세션 시작 실패:', error);
                return false;
            }
        }
        
        /**
         * 소셜 로그인
         */
        async socialLogin(provider, tokenData) {
            try {
                // 브라우저에서 국가/언어 자동 감지
                const userLang = navigator.language || navigator.userLanguage || 'en-US';
                const country = userLang.split('-')[1] || 'US';
                
                const response = await fetch(`${this.apiBase}/social`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        provider: provider,
                        token: tokenData.token,
                        user_data: tokenData.user,
                        country: country,
                        language: userLang
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    this.token = data.token;
                    this.user = data.user;
                    this.isGuest = false;
                    localStorage.setItem('sungsuya_auth_token', data.token);
                    localStorage.removeItem('sungsuya_guest_id');
                    return data;
                }
                return { success: false, message: data.message };
            } catch (error) {
                console.error('소셜 로그인 실패:', error);
                return { success: false, message: '로그인 처리 중 오류가 발생했습니다.' };
            }
        }
        
        /**
         * 이메일 로그인
         */
        async login(email, password) {
            try {
                const response = await fetch(`${this.apiBase}/login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });
                
                const data = await response.json();
                if (data.success) {
                    this.token = data.token;
                    this.user = data.user;
                    this.isGuest = false;
                    localStorage.setItem('sungsuya_auth_token', data.token);
                    localStorage.removeItem('sungsuya_guest_id');
                    return { success: true, user: data.user };
                }
                return { success: false, message: data.message || '로그인에 실패했습니다.' };
            } catch (error) {
                console.error('로그인 실패:', error);
                return { success: false, message: '로그인 중 오류가 발생했습니다.' };
            }
        }
        
        /**
         * 이메일 회원가입
         */
        async emailRegister(email, password, displayName) {
            try {
                const response = await fetch(`${this.apiBase}/register`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password,
                        display_name: displayName
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    this.token = data.token;
                    this.user = data.user;
                    this.isGuest = false;
                    localStorage.setItem('sungsuya_auth_token', data.token);
                    localStorage.removeItem('sungsuya_guest_id');
                    return data;
                }
                return { success: false, message: data.message };
            } catch (error) {
                console.error('회원가입 실패:', error);
                return { success: false, message: '회원가입 처리 중 오류가 발생했습니다.' };
            }
        }
        
        /**
         * 회원가입 (레거시 호환성)
         */
        async register(userData) {
            return this.emailRegister(userData.email, userData.password, userData.display_name);
        }
        
        /**
         * 인증 상태 확인
         */
        async checkAuthStatus() {
            if (!this.token) return false;
            
            try {
                const decoded = jwt_decode(this.token);
                if (!decoded || decoded.exp * 1000 < Date.now()) {
                    this.clearAuth();
                    return false;
                }
                
                const response = await fetch(`${this.apiBase}/status`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${this.token}`
                    }
                });
                
                const data = await response.json();
                if (data.authenticated) {
                    this.user = data.user;
                    this.isGuest = false;
                    return true;
                } else if (data.guest) {
                    this.isGuest = true;
                    this.user = null;
                    return true;
                }
                
                this.clearAuth();
                return false;
            } catch (error) {
                console.error('인증 상태 확인 실패:', error);
                this.clearAuth();
                return false;
            }
        }
        
        /**
         * 프로필 업데이트
         */
        async updateProfile(profileData) {
            if (!this.token || this.isGuest) {
                return { success: false, message: '로그인이 필요합니다.' };
            }
            
            try {
                const response = await fetch(`${this.apiBase}/profile`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${this.token}`
                    },
                    body: JSON.stringify(profileData)
                });
                
                const data = await response.json();
                if (data.success && data.user) {
                    this.user = data.user;
                }
                return data;
            } catch (error) {
                console.error('프로필 업데이트 실패:', error);
                return { success: false, message: '프로필 업데이트 중 오류가 발생했습니다.' };
            }
        }
        
        /**
         * 로그아웃
         */
        logout() {
            this.clearAuth();
            // 게스트 세션 시작
            this.startGuestSession();
        }
        
        /**
         * 인증 정보 삭제
         */
        clearAuth() {
            this.token = null;
            this.user = null;
            this.isGuest = false;
            localStorage.removeItem('sungsuya_auth_token');
            localStorage.removeItem('sungsuya_guest_id');
        }
        
        /**
         * 인증된 요청 보내기
         */
        async authenticatedRequest(url, options = {}) {
            if (!this.token) {
                throw new Error('인증 토큰이 없습니다.');
            }
            
            const headers = {
                ...options.headers,
                'Authorization': `Bearer ${this.token}`
            };
            
            return fetch(url, { ...options, headers });
        }
        
        /**
         * 로그인 필요 여부 확인
         */
        requireAuth(callback, showModal = true) {
            if (this.user && !this.isGuest) {
                return callback();
            }
            
            if (showModal) {
                this.showAuthModal();
            }
            return false;
        }
        
        /**
         * 인증 모달 표시 (최적화된 버전)
         */
        showAuthModal() {
            let modal = document.getElementById('auth-modal');
            
            if (!modal) {
                this.createAuthModal();
                modal = document.getElementById('auth-modal');
            }
            
            // 모달 표시
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // 스크롤 방지
            
            // 포커스 설정 (모바일 최적화)
            setTimeout(() => {
                const firstInput = modal.querySelector('input:not([type="hidden"])');
                if (firstInput && window.innerWidth > 768) {
                    firstInput.focus();
                }
            }, 300);
        }
        
        /**
         * 인증 모달 생성 (최적화된 버전)
         */
        createAuthModal() {
            if (this.modalCreated) return;
            
            const modalHTML = `
                <div id="auth-modal" class="sungsuya-auth-modal">
                    <div class="auth-modal-content">
                        <span class="auth-modal-close">&times;</span>
                        <h2 id="auth-modal-title">로그인</h2>
                        <p id="auth-modal-subtitle">계속하려면 로그인해주세요.</p>
                        
                        <div class="auth-social-buttons">
                            <button class="auth-btn auth-google" onclick="sungsuyaAuth.handleGoogleLogin()">
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <path fill="#4285f4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34a853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#fbbc05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#ea4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                Google로 계속하기
                            </button>
                            
                            <button class="auth-btn auth-kakao" onclick="sungsuyaAuth.handleKakaoLogin()">
                                <svg viewBox="0 0 24 24" width="20" height="20">
                                    <path fill="#000000" d="M12 3C6.48 3 2 6.55 2 10.98c0 2.83 1.82 5.31 4.56 6.75l-.93 3.4c-.08.3.21.57.5.45l4.09-1.73c.58.08 1.17.13 1.78.13 5.52 0 10-3.55 10-7.98S17.52 3 12 3z"/>
                                </svg>
                                카카오로 계속하기
                            </button>
                        </div>
                        
                        <div class="auth-divider">또는</div>
                        
                        <button class="auth-btn auth-email" onclick="sungsuyaAuth.toggleEmailForm()">
                            <span id="email-btn-text">이메일로 로그인</span>
                        </button>
                        
                        <div id="email-form" class="auth-email-form" style="display:none;">
                            <input type="email" id="auth-email" placeholder="이메일" required autocomplete="email">
                            <input type="password" id="auth-password" placeholder="비밀번호" required autocomplete="current-password">
                            <input type="text" id="auth-name" placeholder="닉네임 (회원가입시)" style="display:none;" autocomplete="name">
                            
                            <button class="auth-btn auth-submit" id="email-submit-btn" onclick="sungsuyaAuth.handleEmailSubmit()">
                                로그인
                            </button>
                            
                            <div class="auth-toggle">
                                <span id="auth-toggle-text">계정이 없으신가요?</span>
                                <a href="#" onclick="sungsuyaAuth.toggleAuthMode(); return false;" id="auth-toggle-link">회원가입</a>
                            </div>
                            
                            <div id="auth-error" class="auth-error" style="display:none;"></div>
                        </div>
                        
                        <p class="auth-terms">
                            계속 진행하면 <a href="/terms">이용약관</a> 및 
                            <a href="/privacy">개인정보처리방침</a>에 동의하는 것으로 간주됩니다.
                        </p>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            this.modalCreated = true;
            
            // 이벤트 리스너 설정
            const modal = document.getElementById('auth-modal');
            const closeBtn = modal.querySelector('.auth-modal-close');
            
            // 닫기 버튼
            closeBtn.addEventListener('click', () => this.closeAuthModal());
            
            // 모달 외부 클릭
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeAuthModal();
                }
            });
            
            // 엔터키 처리
            modal.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && e.target.matches('input')) {
                    this.handleEmailSubmit();
                }
            });
        }
        
        /**
         * 모달 닫기
         */
        closeAuthModal() {
            const modal = document.getElementById('auth-modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = ''; // 스크롤 복원
                // 입력 필드 초기화
                this.resetEmailForm();
            }
        }
        
        /**
         * 이메일 폼 토글
         */
        toggleEmailForm() {
            const form = document.getElementById('email-form');
            const isVisible = form.style.display !== 'none';
            
            form.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                // 모바일에서는 포커스 지연
                setTimeout(() => {
                    if (window.innerWidth > 768) {
                        document.getElementById('auth-email').focus();
                    }
                }, 100);
            }
        }
        
        /**
         * 로그인/회원가입 모드 토글
         */
        toggleAuthMode() {
            this.isLoginMode = !this.isLoginMode;
            
            const title = document.getElementById('auth-modal-title');
            const subtitle = document.getElementById('auth-modal-subtitle');
            const nameField = document.getElementById('auth-name');
            const submitBtn = document.getElementById('email-submit-btn');
            const toggleText = document.getElementById('auth-toggle-text');
            const toggleLink = document.getElementById('auth-toggle-link');
            const emailBtnText = document.getElementById('email-btn-text');
            
            if (this.isLoginMode) {
                title.textContent = '로그인';
                subtitle.textContent = '계속하려면 로그인해주세요.';
                nameField.style.display = 'none';
                nameField.required = false;
                submitBtn.textContent = '로그인';
                toggleText.textContent = '계정이 없으신가요?';
                toggleLink.textContent = '회원가입';
                emailBtnText.textContent = '이메일로 로그인';
            } else {
                title.textContent = '회원가입';
                subtitle.textContent = '새 계정을 만들어주세요.';
                nameField.style.display = 'block';
                nameField.required = true;
                submitBtn.textContent = '회원가입';
                toggleText.textContent = '이미 계정이 있으신가요?';
                toggleLink.textContent = '로그인';
                emailBtnText.textContent = '이메일로 회원가입';
            }
            
            // 에러 메시지 초기화
            this.hideError();
        }
        
        /**
         * 이메일 폼 리셋
         */
        resetEmailForm() {
            document.getElementById('auth-email').value = '';
            document.getElementById('auth-password').value = '';
            document.getElementById('auth-name').value = '';
            this.hideError();
            this.isLoginMode = true;
            this.toggleAuthMode();
            this.toggleAuthMode(); // 로그인 모드로 초기화
        }
        
        /**
         * 에러 표시
         */
        showError(message) {
            const errorDiv = document.getElementById('auth-error');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
        
        /**
         * 에러 숨김
         */
        hideError() {
            const errorDiv = document.getElementById('auth-error');
            if (errorDiv) {
                errorDiv.style.display = 'none';
            }
        }
        
        /**
         * 이메일 제출 처리
         */
        async handleEmailSubmit() {
            const email = document.getElementById('auth-email').value.trim();
            const password = document.getElementById('auth-password').value;
            const name = document.getElementById('auth-name').value.trim();
            
            if (!email || !password) {
                this.showError('이메일과 비밀번호를 입력해주세요.');
                return;
            }
            
            // 이메일 유효성 검사
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                this.showError('올바른 이메일 주소를 입력해주세요.');
                return;
            }
            
            // 비밀번호 최소 길이 체크 (회원가입시만)
            if (!this.isLoginMode && password.length < 6) {
                this.showError('비밀번호는 6자 이상이어야 합니다.');
                return;
            }
            
            // 버튼 비활성화 (중복 제출 방지)
            const submitBtn = document.getElementById('email-submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = '처리중...';
            
            try {
                let result;
                
                if (this.isLoginMode) {
                    // 로그인
                    result = await this.login(email, password);
                } else {
                    // 회원가입
                    if (!name && !this.isLoginMode) {
                        this.showError('닉네임을 입력해주세요.');
                        submitBtn.disabled = false;
                        submitBtn.textContent = '회원가입';
                        return;
                    }
                    result = await this.emailRegister(email, password, name || email.split('@')[0]);
                }
                
                if (result.success) {
                    this.onLoginSuccess();
                } else {
                    this.showError(result.message || (this.isLoginMode ? '로그인에 실패했습니다.' : '회원가입에 실패했습니다.'));
                }
            } catch (error) {
                console.error('인증 처리 오류:', error);
                this.showError('처리 중 오류가 발생했습니다. 다시 시도해주세요.');
            } finally {
                // 버튼 다시 활성화
                submitBtn.disabled = false;
                submitBtn.textContent = this.isLoginMode ? '로그인' : '회원가입';
            }
        }
        
        /**
         * Google 로그인 처리
         */
        async handleGoogleLogin() {
            // 로컬 환경 체크
            if (window.IS_LOCAL_ENV) {
                this.showError('로컬 환경(.local)에서는 Google 로그인을 사용할 수 없습니다.\n실서버에서 테스트하거나 ngrok를 사용하세요.');
                return;
            }
            
            // Google Sign-In API 사용
            if (typeof google !== 'undefined' && google.accounts) {
                google.accounts.id.initialize({
                    client_id: window.GOOGLE_CLIENT_ID,
                    callback: async (response) => {
                        const result = await this.socialLogin('google', {
                            token: response.credential,
                            user: jwt_decode(response.credential)
                        });
                        
                        if (result.success) {
                            this.onLoginSuccess();
                        } else {
                            this.showError(result.message || '로그인에 실패했습니다.');
                        }
                    }
                });
                google.accounts.id.prompt();
            } else {
                this.showError('Google 로그인을 사용할 수 없습니다.');
            }
        }
        
        /**
         * 카카오 로그인 처리
         */
        async handleKakaoLogin() {
            if (typeof Kakao !== 'undefined') {
                Kakao.Auth.login({
                    success: async (authObj) => {
                        // 사용자 정보 요청
                        Kakao.API.request({
                            url: '/v2/user/me',
                            success: async (res) => {
                                const result = await this.socialLogin('kakao', {
                                    token: authObj.access_token,
                                    user: res
                                });
                                
                                if (result.success) {
                                    this.onLoginSuccess();
                                } else {
                                    this.showError(result.message || '로그인에 실패했습니다.');
                                }
                            },
                            fail: (error) => {
                                console.error('카카오 사용자 정보 요청 실패:', error);
                                this.showError('로그인에 실패했습니다.');
                            }
                        });
                    },
                    fail: (error) => {
                        console.error('카카오 로그인 실패:', error);
                        this.showError('로그인에 실패했습니다.');
                    }
                });
            } else {
                this.showError('카카오 로그인을 사용할 수 없습니다.');
            }
        }
        
        /**
         * 로그인 성공 처리
         */
        onLoginSuccess() {
            this.closeAuthModal();
            
            // 헤더 UI 업데이트
            this.updateHeaderUI();
            
            // 페이지 새로고침 또는 상태 업데이트
            if (window.onAuthSuccess) {
                window.onAuthSuccess(this.user);
            } else {
                // 기본 동작: 페이지 새로고침
                location.reload();
            }
        }
        
        /**
         * 토스트 메시지 표시
         */
        showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'auth-toast';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        
        /**
         * 헤더 UI 업데이트
         */
        updateHeaderUI() {
            // 기존 로그인 버튼을 사용자 정보로 교체
            const loginBtns = document.querySelectorAll('.login-btn');
            loginBtns.forEach(btn => {
                if (this.user && !this.isGuest) {
                    // 사용자 메뉴로 교체
                    const userMenu = document.createElement('div');
                    userMenu.className = 'user-menu';
                    userMenu.innerHTML = `
                        <button class="user-menu-toggle">
                            <i data-feather="user"></i>
                            <span>${this.user.display_name}</span>
                            <i data-feather="chevron-down"></i>
                        </button>
                        <div class="user-dropdown">
                            <a href="/my-account" class="dropdown-item">
                                <i data-feather="user"></i>
                                <span>내 정보</span>
                            </a>
                            <a href="/my-tours" class="dropdown-item">
                                <i data-feather="map"></i>
                                <span>내 투어</span>
                            </a>
                            <a href="/my-reviews" class="dropdown-item">
                                <i data-feather="star"></i>
                                <span>내 리뷰</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item" onclick="sungsuyaAuth.logout(); location.reload(); return false;">
                                <i data-feather="log-out"></i>
                                <span>로그아웃</span>
                            </a>
                        </div>
                    `;
                    btn.parentNode.replaceChild(userMenu, btn);
                    
                    // Feather 아이콘 초기화
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }
            });
        }
    }

    // 전역 인스턴스 생성
    window.sungsuyaAuth = new SungsuyaAuth();
    
    // 전역으로 클래스도 노출 (필요한 경우를 위해)
    window.SungsuyaAuth = SungsuyaAuth;
    
    // PWA 환경에서 자동으로 게스트 세션 시작
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.sungsuyaAuth.token) {
            window.sungsuyaAuth.startGuestSession();
        }
        
        // 헤더에 사용자 정보 표시
        window.sungsuyaAuth.updateHeaderUI();
    });

})(); // IIFE 종료