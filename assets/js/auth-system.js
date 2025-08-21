/**
 * 성수야 PWA 인증 시스템 - 프론트엔드 컴포넌트
 * 
 * @version 2.0.0
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
                return { success: false, message: data.message };
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
         * 인증 모달 표시
         */
        showAuthModal() {
            const modal = document.getElementById('auth-modal');
            if (modal) {
                modal.style.display = 'block';
            } else {
                // 모달이 없으면 생성
                this.createAuthModal();
            }
        }
        
        /**
         * 인증 모달 생성
         */
        createAuthModal() {
            const modalHTML = `
                <div id="auth-modal" class="sungsuya-auth-modal">
                    <div class="auth-modal-content">
                        <span class="auth-modal-close">&times;</span>
                        <h2>로그인이 필요합니다</h2>
                        <p>이 기능을 사용하려면 로그인해주세요.</p>
                        
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
                        
                        <button class="auth-btn auth-email" onclick="sungsuyaAuth.showEmailForm()">
                            이메일로 가입하기
                        </button>
                        
                        <div id="email-form" class="auth-email-form" style="display:none;">
                            <input type="email" id="auth-email" placeholder="이메일" required>
                            <input type="password" id="auth-password" placeholder="비밀번호" required>
                            <input type="text" id="auth-name" placeholder="닉네임 (선택)">
                            <button class="auth-btn auth-submit" onclick="sungsuyaAuth.handleEmailRegister()">
                                가입하기
                            </button>
                        </div>
                        
                        <p class="auth-terms">
                            계속 진행하면 <a href="/terms">이용약관</a> 및 
                            <a href="/privacy">개인정보처리방침</a>에 동의하는 것으로 간주됩니다.
                        </p>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // 모달 닫기 이벤트
            document.querySelector('.auth-modal-close').addEventListener('click', () => {
                document.getElementById('auth-modal').style.display = 'none';
            });
        }
        
        /**
         * Google 로그인 처리
         */
        async handleGoogleLogin() {
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
                            alert(result.message || '로그인에 실패했습니다.');
                        }
                    }
                });
                google.accounts.id.prompt();
            } else {
                alert('Google 로그인을 사용할 수 없습니다.');
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
                                    alert(result.message || '로그인에 실패했습니다.');
                                }
                            },
                            fail: (error) => {
                                console.error('카카오 사용자 정보 요청 실패:', error);
                                alert('로그인에 실패했습니다.');
                            }
                        });
                    },
                    fail: (error) => {
                        console.error('카카오 로그인 실패:', error);
                        alert('로그인에 실패했습니다.');
                    }
                });
            } else {
                alert('카카오 로그인을 사용할 수 없습니다.');
            }
        }
        
        /**
         * 이메일 폼 표시
         */
        showEmailForm() {
            document.getElementById('email-form').style.display = 'block';
        }
        
        /**
         * 이메일 회원가입 처리
         */
        async handleEmailRegister() {
            const email = document.getElementById('auth-email').value;
            const password = document.getElementById('auth-password').value;
            const name = document.getElementById('auth-name').value;
            
            if (!email || !password) {
                alert('이메일과 비밀번호를 입력해주세요.');
                return;
            }
            
            const result = await this.emailRegister(email, password, name);
            
            if (result.success) {
                this.onLoginSuccess();
            } else {
                alert(result.message || '회원가입에 실패했습니다.');
            }
        }
        
        /**
         * 로그인 성공 처리
         */
        onLoginSuccess() {
            const modal = document.getElementById('auth-modal');
            if (modal) {
                modal.style.display = 'none';
            }
            
            // 헤더 UI 업데이트
            this.updateHeaderUI();
            
            // 국가 정보가 없으면 부드럽게 묻기 (Progressive Profiling)
            if (this.user && !this.user.country) {
                setTimeout(() => {
                    this.showCountryPrompt();
                }, 2000); // 2초 후에 표시
            }
            
            // 페이지 새로고침 또는 상태 업데이트
            if (window.onAuthSuccess) {
                window.onAuthSuccess(this.user);
            }
        }
        
        /**
         * 국가 선택 프롬프트
         */
        showCountryPrompt() {
            // 현재 GTranslate 언어 확인
            const currentGTranslateLang = document.querySelector('.gtranslate_wrapper .gt-current-lang')?.getAttribute('data-gt-lang') || 'ko';
            
            const promptHTML = `
                <div class="profile-prompt" id="country-prompt">
                    <h3>🌏 어느 나라에서 오셨나요?</h3>
                    <p>더 나은 서비스를 위해 알려주세요. (언어 설정과는 별개입니다)</p>
                    <div class="country-options">
                        <button onclick="sungsuyaAuth.selectCountry('KR', '🇰🇷 한국')">🇰🇷 한국</button>
                        <button onclick="sungsuyaAuth.selectCountry('US', '🇺🇸 미국')">🇺🇸 미국</button>
                        <button onclick="sungsuyaAuth.selectCountry('JP', '🇯🇵 일본')">🇯🇵 일본</button>
                        <button onclick="sungsuyaAuth.selectCountry('CN', '🇨🇳 중국')">🇨🇳 중국</button>
                    </div>
                    
                    <div class="other-country-section">
                        <button class="btn-other-country" onclick="sungsuyaAuth.showCountryInput()">
                            🌍 다른 나라
                        </button>
                        <div id="country-input-wrapper" style="display: none;">
                            <select id="country-select" class="country-select">
                                <option value="">국가를 선택하세요</option>
                                <option value="VN">🇻🇳 베트남</option>
                                <option value="TH">🇹🇭 태국</option>
                                <option value="SG">🇸🇬 싱가포르</option>
                                <option value="MY">🇲🇾 말레이시아</option>
                                <option value="ID">🇮🇩 인도네시아</option>
                                <option value="PH">🇵🇭 필리핀</option>
                                <option value="IN">🇮🇳 인도</option>
                                <option value="GB">🇬🇧 영국</option>
                                <option value="FR">🇫🇷 프랑스</option>
                                <option value="DE">🇩🇪 독일</option>
                                <option value="ES">🇪🇸 스페인</option>
                                <option value="IT">🇮🇹 이탈리아</option>
                                <option value="RU">🇷🇺 러시아</option>
                                <option value="AU">🇦🇺 호주</option>
                                <option value="CA">🇨🇦 캐나다</option>
                                <option value="BR">🇧🇷 브라질</option>
                                <option value="MX">🇲🇽 멕시코</option>
                                <option value="AR">🇦🇷 아르헨티나</option>
                                <option value="OTHER">기타</option>
                            </select>
                            <input type="text" id="country-text" class="country-input" 
                                   placeholder="국가명을 입력하세요" style="display: none;">
                            <button class="btn-confirm" onclick="sungsuyaAuth.confirmCountry()">
                                확인
                            </button>
                        </div>
                    </div>
                    
                    <p class="note">💡 언어 변경은 상단 메뉴에서 가능합니다</p>
                    <button class="btn-skip" onclick="document.getElementById('country-prompt').remove()">
                        나중에
                    </button>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', promptHTML);
            
            // 셀렉트 박스 변경 이벤트
            document.getElementById('country-select').addEventListener('change', function(e) {
                const textInput = document.getElementById('country-text');
                if (e.target.value === 'OTHER') {
                    textInput.style.display = 'block';
                    textInput.focus();
                } else {
                    textInput.style.display = 'none';
                    textInput.value = '';
                }
            });
        }
        
        /**
         * 다른 나라 입력 폼 표시
         */
        showCountryInput() {
            document.getElementById('country-input-wrapper').style.display = 'block';
            document.querySelector('.btn-other-country').style.display = 'none';
        }
        
        /**
         * 국가 확인 처리
         */
        confirmCountry() {
            const select = document.getElementById('country-select');
            const textInput = document.getElementById('country-text');
            
            let countryCode = select.value;
            let countryName = '';
            
            if (countryCode === 'OTHER' && textInput.value) {
                countryCode = 'OTHER';
                countryName = textInput.value;
            } else if (countryCode && countryCode !== 'OTHER') {
                countryName = select.options[select.selectedIndex].text;
            } else {
                this.showToast('국가를 선택하거나 입력해주세요.');
                return;
            }
            
            this.selectCountry(countryCode, countryName, countryName);
        }
        
        /**
         * 국가 선택 처리 (GTranslate 연동)
         */
        async selectCountry(code, displayName, fullName = null) {
            // 현재 GTranslate 언어 함께 저장
            const currentLang = document.querySelector('.gtranslate_wrapper .gt-current-lang')?.getAttribute('data-gt-lang') || 'ko';
            
            const profileData = {
                user_country: code,
                preferred_language: navigator.language,
                gtranslate_preference: currentLang
            };
            
            // 기타 국가인 경우 실제 국가명도 저장
            if (fullName) {
                profileData.user_country_name = fullName;
            }
            
            const result = await this.updateProfile(profileData);
            
            if (result.success) {
                const prompt = document.getElementById('country-prompt');
                if (prompt) prompt.remove();
                
                // 국가와 현재 언어가 다른 경우 부드럽게 제안
                const suggestedLang = this.getSuggestedLanguage(code);
                if (suggestedLang && suggestedLang !== currentLang && code !== 'OTHER') {
                    setTimeout(() => {
                        this.suggestLanguageChange(code, suggestedLang);
                    }, 1000);
                } else {
                    this.showToast(`${displayName}을(를) 선택하셨습니다. 감사합니다!`);
                }
            }
        }
        
        /**
         * 국가별 추천 언어 (제안용)
         */
        getSuggestedLanguage(countryCode) {
            const suggestions = {
                'KR': 'ko',
                'US': 'en',
                'JP': 'ja',
                'CN': 'zh-CN'
            };
            return suggestions[countryCode];
        }
        
        /**
         * 언어 변경 제안 (강제하지 않음)
         */
        suggestLanguageChange(countryCode, suggestedLang) {
            const langNames = {
                'ko': '한국어',
                'en': 'English',
                'ja': '日本語',
                'zh-CN': '中文'
            };
            
            const suggestionHTML = `
                <div class="language-suggestion" id="lang-suggestion">
                    <p>${langNames[suggestedLang]}로 보시겠습니까?</p>
                    <div class="suggestion-buttons">
                        <button onclick="window.changeGTranslateLang('${suggestedLang}'); document.getElementById('lang-suggestion').remove();">
                            네, 변경합니다
                        </button>
                        <button onclick="document.getElementById('lang-suggestion').remove();">
                            현재 언어 유지
                        </button>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', suggestionHTML);
            
            // 5초 후 자동으로 사라짐
            setTimeout(() => {
                const suggestion = document.getElementById('lang-suggestion');
                if (suggestion) suggestion.remove();
            }, 5000);
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
            const accountDiv = document.getElementById('user-account');
            if (!accountDiv) return;
            
            if (this.user && !this.isGuest) {
                // 로그인 상태
                accountDiv.innerHTML = `
                    <div class="user-profile-mini" onclick="sungsuyaAuth.showUserMenu()">
                        <img src="${this.user.avatar || '/wp-content/themes/sungsuya-v2-theme/assets/images/default-avatar.png'}" 
                             alt="${this.user.display_name}" class="user-avatar">
                        <span class="user-name">${this.user.display_name}</span>
                    </div>
                `;
            } else if (this.isGuest) {
                // 게스트 상태
                accountDiv.innerHTML = `
                    <div class="guest-mode-indicator" onclick="sungsuyaAuth.showAuthModal()">
                        게스트 모드 • 로그인
                    </div>
                `;
            } else {
                // 비로그인 상태
                accountDiv.innerHTML = `
                    <button class="login-button" onclick="sungsuyaAuth.showAuthModal()">
                        로그인
                    </button>
                `;
            }
        }
        
        /**
         * 사용자 메뉴 표시
         */
        showUserMenu() {
            // 간단한 드롭다운 메뉴 (추후 구현)
            if (confirm('로그아웃 하시겠습니까?')) {
                this.logout();
                this.updateHeaderUI();
            }
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
