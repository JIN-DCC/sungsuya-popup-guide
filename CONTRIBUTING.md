# 🤝 SUNGSUYA 프로젝트 기여 가이드

SUNGSUYA 프로젝트에 관심을 가져주셔서 감사합니다! 이 가이드는 우리의 혁신적인 **3-AI 협업 환경**에서 어떻게 기여할 수 있는지 설명합니다.

## 🎯 프로젝트 개요

SUNGSUYA는 성수동 팝업스토어 가이드 PWA로, **Claude Desktop + ChatGPT Plus + Gemini CLI**의 협업으로 개발되는 혁신적인 프로젝트입니다.

### 🏆 현재 성과
- **⚡ 67% 성능 최적화 완료**: functions.php 40.83KB → 13.47KB
- **📍 100% 정확한 위치**: 35개 장소 좌표 완전 검증
- **📱 완전한 PWA**: 오프라인 지원 + 앱 설치 가능
- **🤖 3-AI 협업**: 혁신적 개발 속도 + 안정적 품질

## 🤖 3-AI 협업 시스템

우리 프로젝트의 핵심은 각 AI의 전문성을 활용한 협업입니다:

### 🖥️ Claude Desktop (프로젝트 매니저)
- **역할**: 코드 품질 관리, 성능 최적화, 통합 관리
- **브랜치**: `feature/claude-optimizations`
- **환경**: 로컬 파일 직접 접근

### 💬 ChatGPT Plus (기능 개발자)
- **역할**: 신기능 개발, 창의적 솔루션, UI/UX 개발
- **브랜치**: `feature/chatgpt-features`
- **협업**: 실시간 피드백 기반 개발

### ⚡ Gemini CLI (DevOps 엔지니어)
- **역할**: 배포 자동화, 시스템 관리, CI/CD 구축
- **브랜치**: `feature/gemini-deployment`
- **환경**: 터미널 기반 자동화

## 🚀 기여 방법

### 1. 🎯 일반 개발자 기여

#### 📋 기본 워크플로우
```bash
# 1. Repository Fork
git clone https://github.com/[your-username]/sungsuya-popup-guide.git
cd sungsuya-popup-guide

# 2. 개발 환경 설정
npm install
cp wp-config-sample.php wp-config.php
# 데이터베이스 정보 입력

# 3. 새 기능 브랜치 생성
git checkout -b feature/amazing-feature

# 4. 개발 작업
# 코드 작성...

# 5. 테스트
npm test
npm run pwa-build

# 6. 커밋 및 푸시
git add .
git commit -m "Add amazing feature"
git push origin feature/amazing-feature

# 7. Pull Request 생성
```

#### 🎨 기여 가능 영역
- **🏪 팝업스토어 기능**: 검색, 필터링, 리뷰 시스템
- **📱 PWA 개선**: 오프라인 기능, 푸시 알림
- **🎨 UI/UX**: 반응형 디자인, 사용성 개선
- **⚡ 성능**: 최적화, 로딩 속도 개선
- **🌐 다국어**: 한국어, 영어, 일본어, 중국어 지원
- **📊 분석**: 사용자 행동 분석, 성능 모니터링

### 2. 🤖 AI 협업 참여

AI 개발자들은 각자의 전문 영역에서 기여할 수 있습니다:

#### Claude Desktop 협업
```yaml
참여 방법:
  - 로컬 환경 직접 접근하여 코드 품질 관리
  - 성능 최적화 작업 (67% 기준 유지)
  - 다른 AI 작업 통합 및 테스트
  
브랜치: feature/claude-optimizations
역할: 프로젝트 매니저 + 품질 관리자
```

#### ChatGPT Plus 협업
```yaml
참여 방법:
  - 혁신적인 기능 아이디어 제안 및 구현
  - UI/UX 컴포넌트 개발
  - 창의적 문제 해결
  
브랜치: feature/chatgpt-features  
역할: 기능 개발자 + 창의적 솔루션
```

#### Gemini CLI 협업
```yaml
참여 방법:
  - 배포 자동화 스크립트 개발
  - CI/CD 파이프라인 구축
  - 성능 모니터링 시스템 구축
  
브랜치: feature/gemini-deployment
역할: DevOps + 시스템 관리자
```

## 📋 코딩 표준

### 🏗️ 아키텍처 원칙
- **모듈화**: inc/ 디렉토리 구조 활용
- **성능 우선**: 67% 최적화 기준 유지
- **PWA 지원**: 모든 기능에서 오프라인 고려
- **반응형**: 모바일 우선 개발

### 💻 PHP 표준 (WordPress)
```php
// 함수 명명: snake_case
function sungsuya_get_popup_stores() {
    // WordPress 표준 준수
}

// 클래스 명명: PascalCase  
class SungsuyaPopupStore {
    // 모듈화된 구조
}

// 상수: UPPER_CASE
define('SUNGSUYA_VERSION', '2.3.0');
```

### 🎨 CSS/JavaScript 표준
```css
/* CSS: BEM 방법론 */
.sungsuya-popup-card {
    /* 성능 최적화된 CSS */
}

.sungsuya-popup-card__title {
    /* 모바일 우선 반응형 */
}
```

```javascript
// JavaScript: camelCase
function loadPopupStores() {
    // PWA 호환 코드
    // Service Worker 연동 고려
}
```

### 📱 PWA 개발 표준
```javascript
// Service Worker 등록
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw-v2.js');
}

// 오프라인 지원
cache.addAll([
    '/',
    '/wp-content/themes/sungsuya-v2-theme/assets/css/main.css',
    '/wp-content/themes/sungsuya-v2-theme/assets/js/main.js'
]);
```

## 🧪 테스트 가이드

### 🔍 필수 테스트
- **기능 테스트**: 모든 팝업스토어 기능 정상 동작
- **성능 테스트**: 로딩 속도 2초 이하 유지
- **모바일 테스트**: iOS/Android 호환성
- **PWA 테스트**: 오프라인 모드, 설치 가능
- **보안 테스트**: WordPress 보안 기준 준수

### 🤖 자동화된 테스트
```bash
# PHP 문법 검사
find wp-content/themes/sungsuya-v2-theme -name "*.php" -exec php -l {} \;

# JavaScript 테스트
npm test

# PWA 검증
npm run pwa-build
lighthouse --view http://localhost:8080

# 성능 테스트
npm run performance-test
```

## 📊 성능 기준

### ⚡ 67% 최적화 기준
현재 프로젝트는 67% 성능 최적화를 달성했습니다. 모든 기여는 이 기준을 유지해야 합니다:

```
📊 성능 지표:
├── functions.php: 13.47KB (목표: <20KB)
├── CSS 총 크기: <100KB
├── JavaScript: <50KB  
├── 이미지 최적화: WebP 사용
└── 로딩 속도: <2초
```

### 🏥 품질 체크리스트
- [ ] **WordPress 표준**: PHPCS 통과
- [ ] **성능**: Lighthouse 점수 90+ 
- [ ] **접근성**: WCAG 2.1 AA 준수
- [ ] **SEO**: 메타데이터 완성도
- [ ] **보안**: 취약점 0개

## 🚀 배포 프로세스

### 🌊 브랜치 전략
```
main (프로덕션)
├── staging (스테이징)
├── develop (개발 통합)
│   ├── feature/claude-optimizations
│   ├── feature/chatgpt-features
│   └── feature/gemini-deployment
└── hotfix/ (긴급 수정)
```

### 📋 PR 검토 과정
1. **자동 테스트**: GitHub Actions 통과 필수
2. **Claude Desktop**: 코드 품질 및 성능 검토
3. **동료 검토**: 최소 1명의 승인 필요
4. **통합 테스트**: 스테이징 환경 배포 테스트
5. **최종 승인**: 3-AI 협업팀 승인

### 🎯 배포 기준
- [ ] 모든 테스트 통과
- [ ] 성능 기준 유지 (67% 최적화)
- [ ] 보안 검사 통과
- [ ] 문서 업데이트 완료
- [ ] 3-AI 협업팀 승인

## 📝 이슈 및 PR 가이드

### 🐛 버그 리포트
[Bug Report 템플릿](.github/ISSUE_TEMPLATE/bug_report.md) 사용:
- 재현 방법 명시
- 환경 정보 포함
- 우선순위 설정
- 스크린샷 첨부

### ✨ 기능 요청
[Feature Request 템플릿](.github/ISSUE_TEMPLATE/feature_request.md) 사용:
- 명확한 요구사항
- 예상 효과 설명
- UI/UX 요구사항
- 담당 AI 제안

### 🤖 AI 협업 이슈
[AI Collaboration 템플릿](.github/ISSUE_TEMPLATE/ai_collaboration.md) 사용:
- 3-AI 역할 분담
- 협업 워크플로우
- 성공 지표 정의
- 위험 요소 대응

### 📋 PR 제목 규칙
```
[타입] 간단한 설명

타입:
- feat: 새로운 기능
- fix: 버그 수정  
- perf: 성능 개선
- refactor: 코드 리팩토링
- docs: 문서 업데이트
- test: 테스트 추가/수정
- style: 코드 스타일 수정
- ai: 3-AI 협업 관련

예시:
- feat: 실시간 팝업스토어 알림 시스템 추가
- fix: 모바일에서 위치 정보 오류 수정
- perf: functions.php 로딩 속도 15% 개선
- ai: ChatGPT Plus 신기능 개발 완료
```

## 🌟 커뮤니티

### 💬 소통 채널
- **GitHub Issues**: 기술적 논의 및 버그 리포트
- **GitHub Discussions**: 아이디어 공유 및 질문
- **GitHub Wiki**: 상세 문서 및 가이드

### 🎉 기여 인정
우리는 모든 기여자를 소중하게 여깁니다:
- **README.md**: 기여자 목록에 추가
- **CHANGELOG.md**: 기여 내역 기록
- **GitHub Contributors**: 자동 인식
- **Special Thanks**: 주요 기여자 특별 감사

### 🏆 기여 레벨
- **🌱 Contributor**: 첫 PR 머지
- **🌿 Regular**: 5개 이상 PR 머지
- **🌳 Maintainer**: 지속적 기여 및 리뷰 참여
- **🤖 AI Collaborator**: 3-AI 협업 참여

## 📞 도움 요청

### 🆘 어디서 도움을 받을 수 있나요?
- **기술적 질문**: [GitHub Issues](../../issues)에 `question` 라벨로 생성
- **3-AI 협업**: [AI Collaboration 이슈](../../issues/new?template=ai_collaboration.md) 생성
- **일반적 논의**: [GitHub Discussions](../../discussions) 활용

### 📚 참고 자료
- **개발 가이드**: [sungsuya-guide-v2.0.md](docs/sungsuya-guide-v2.0.md)
- **3-AI 협업 보고서**: [sungsuya-report-v2.3.md](docs/sungsuya-report-v2.3.md)
- **WordPress 표준**: [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- **PWA 가이드**: [Progressive Web Apps](https://web.dev/progressive-web-apps/)

---

## 🎊 환영합니다!

SUNGSUYA 프로젝트는 **성수동 팝업스토어 시장을 혁신**하고자 합니다. 여러분의 기여로 더 나은 사용자 경험을 만들어갈 수 있습니다.

**질문이 있으시면 언제든지 [이슈](../../issues)를 생성해주세요!**

---

*Made with ❤️ by 3-AI Collaboration (Claude Desktop + ChatGPT Plus + Gemini CLI)*