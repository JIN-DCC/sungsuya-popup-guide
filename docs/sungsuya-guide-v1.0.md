# SUNGSUYA 프로젝트 지침서 (원본 버전)

> ⚠️ **중요 안내**: 이 문서는 프로젝트 초기 설정 상태를 기록한 원본 버전입니다.  
> ✅ **최신 정보**는 `sungsuya-guide-v1.5.md`를 참조하세요.  
> 📋 **변경 이력**: v1.0~v1.4까지의 모든 업데이트가 최신 버전에 반영되어 있습니다.

---

## 🎯 프로젝트 개요
- **사이트명**: sungsuya.com
- **테마명**: 성수야! (sungsuya-v2-theme)
- **설명**: 성수동 팝업스토어 가이드 PWA - 반응형 디자인
- **버전**: 2.1.0 (functions.php 기준)
- **개발팀**: AI Development Team + 인간 개발자 협업

## 🌐 환경 정보

### 로컬 개발환경
- **도메인**: https://sungsuya.local (HTTP도 가능)
- **도구**: Local WP
- **로그인**: https://sungsuya.local/wp-login.php (관리자 로그인 필요)
- **관리자**: https://sungsuya.local/wp-admin (로그인 후 접근 가능)
- **서버**: nginx
- **PHP**: 8.2.27
- **MySQL**: 8.0.35

### 프로덕션 환경
- **도메인**: sungsuya.com
- **호스팅**: FastComet
- **실서버 접속**: cPanel 사용

## 📁 파일 구조

### 주요 경로
```
로컬 개발:
C:\Users\tjdxo\Local Sites\sungsuya\
├── app\public\                          # 워드프레스 루트
│   ├── wp-content\themes\sungsuya-v2-theme\  # 커스텀 테마
│   └── wp-config.php                    # 설정 파일

백업/원본:
C:\Project\sungsuya\
├── wordpress\                           # 원본 사이트 파일
├── database\                           # SQL 백업 파일
└── docs\                               # 프로젝트 문서
```

### 테마 파일 구조
```
sungsuya-v2-theme/
├── style.css                           # 워드프레스 테마 인식용
├── functions.php                       # 테마 기능 (v2.1.0)
├── front-page.php                      # 메인페이지 (v8.0.0)
├── header.php, footer.php              # 헤더/푸터
├── single-places.php                   # 장소 상세페이지
├── archive-popup-store.php             # 팝업스토어 목록
├── page-tour-planner-pwa.php          # 투어플래너 PWA
├── assets/
│   ├── css/
│   │   ├── main.css                    # 메인 스타일
│   │   ├── variables.css               # CSS 변수 시스템
│   │   ├── design-system.css           # 디자인 시스템
│   │   ├── mobile-optimize.css         # 모바일 최적화
│   │   └── components.css              # 컴포넌트 스타일
│   ├── js/                             # JavaScript 파일
│   ├── images/                         # 이미지 리소스
│   └── fonts/                          # 웹폰트
├── template-parts/                     # 템플릿 부분들
├── pwa/                               # PWA 관련 파일
└── [많은 백업 파일들]                  # 개발 과정의 백업들
```

## 🗄️ 데이터베이스 정보

### 로컬 환경
- **데이터베이스명**: local
- **사용자명**: root
- **비밀번호**: root
- **호스트**: localhost
- **테이블 접두사**: qNDPBNfV_

### 주요 테이블
- qNDPBNfV_options (사이트 설정)
- qNDPBNfV_posts (게시물/페이지)
- qNDPBNfV_postmeta (게시물 메타데이터)

## 🛠️ 개발 설정

### wp-config.php 주요 설정
```php
// 로컬 개발 설정
define('WP_HOME','http://sungsuya.local');
define('WP_SITEURL','http://sungsuya.local');

// 디버그 모드 (개발환경)
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// 캐시 비활성화 (개발환경)
// define('WP_CACHE', true); // 주석처리됨
```

## 🎨 디자인 가이드라인

### 브랜딩
- **브랜드명**: "성수야!"
- **로고**: 성수야! | SUNGSUYA
- **컨셉**: 서울/성수동 팝업스토어 가이드

### 디자인 시스템
- **그라디언트 브랜딩**: 
  - Primary: #667eea → #764ba2
  - Secondary: #f093fb → #f5576c  
  - Accent: #4facfe → #00f2fe
- **타이포그래피**: Pretendard 기본, "SEOUL GUIDE" 영문 타이틀
- **반응형 브레이크포인트**: 
  - Mobile: ~640px
  - Tablet: 640px-1024px
  - Desktop: 1024px+
- **다크모드 지원**: prefers-color-scheme 및 .dark 클래스
- **CSS 변수 시스템**: 완전한 디자인 토큰 체계

## 📋 주요 기능 및 페이지

### 커스텀 포스트 타입
- **places**: 장소 정보 (메타: place_type으로 popup_store 구분)
- **popup_store**: 팝업스토어 정보
- **tour**: 투어 정보

### 주요 페이지 템플릿
- `front-page.php`: 메인페이지
- `single-places.php`: 장소 상세
- `archive-popup-store.php`: 팝업스토어 목록
- `page-tour-planner-pwa.php`: PWA 투어플래너
- `page-about-us.php`: 소개페이지
- `page-contact.php`: 연락처 페이지

### PWA 기능
- `pwa/` 폴더에 PWA 관련 파일들
- 오프라인 지원
- 모바일 앱 같은 경험
- Service Worker (sw-v2.js)
- 투어플래너 PWA 전용 페이지

## ⚠️ 개발 시 주의사항

### 백업 파일 관리
- **매우 많은 백업 파일들 존재**:
  - functions-backup.php, functions-broken.php 등
  - front-page-backup-*.php (여러 버전)
  - header/footer 백업들
- 작업 전 현재 파일 백업 권장
- 백업 파일명에 날짜 포함 권장
- 정리 필요: 불필요한 백업 파일들 정리 고려

### 배포 시 확인사항
1. wp-config.php의 URL을 프로덕션으로 변경
2. 디버그 모드 비활성화
3. 캐시 설정 활성화
4. 데이터베이스 연결 정보 변경

### 성능 최적화
- 이미지 최적화
- CSS/JS 미니파이
- 캐시 설정
- CDN 고려

## 🚀 개발 워크플로우

### 로컬 개발
1. Local WP에서 사이트 시작
2. VS Code로 테마 파일 편집
3. 브라우저에서 실시간 확인
4. 변경사항 백업

### 배포 프로세스
1. 로컬에서 테스트 완료
2. 파일을 FastComet에 업로드
3. 데이터베이스 동기화 (필요시)
4. 프로덕션 테스트

## 📞 협업 정보

### AI 개발 도우미와 협업 시
- 파일 경로는 정확히 명시
- 백업 전 주요 변경사항 안내
- 테스트 결과 공유
- 문제 발생 시 상세한 에러 로그 제공

### 로그인 정보
- **관리자 로그인**: https://sungsuya.local/wp-login.php
- **주의**: wp-admin으로 직접 접근 시 접속 에러 발생 (로그인 필수)
- **AI 개발자 안내**: 관리자 접근이 필요한 경우 인간 개발자에게 로그인 요청
- **로그인 후**: 관리자 대시보드 정상 접근 가능

### 주요 명령어
```bash
# Local WP 사이트 재시작
Local WP > Stop site > Start site

# 디버그 로그 확인
tail -f wp-content/debug.log
```

---

**작성일**: 2025년 8월 1일  
**최종 업데이트**: 프로젝트 초기 설정 완료  
**상태**: 개발 환경 구축 완료, 본격 개발 시작 준비