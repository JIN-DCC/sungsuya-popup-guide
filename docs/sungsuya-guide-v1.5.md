# SUNGSUYA 개발 환경 가이드

**성수동 팝업스토어 가이드 PWA - 개발자를 위한 완전 가이드**

---

## 🎯 프로젝트 개요

- **사이트명**: sungsuya.com
- **테마명**: 성수야! (sungsuya-v2-theme)
- **설명**: 성수동 팝업스토어 가이드 PWA - 반응형 디자인
- **버전**: 2.1.0 (functions.php 기준)
- **개발팀**: AI Development Team + 인간 개발자 협업
- **현재 상태**: v1.4 - 완전한 개발 환경 구축 완료

## 🌐 개발 환경

### 로컬 개발환경
- **URL**: https://sungsuya.local (HTTP도 가능)
- **도구**: Local WP
- **서버**: nginx
- **PHP**: 8.2.27
- **MySQL**: 8.0.35
- **상태**: 완전 정상 작동 (v1.4에서 모든 문제 해결)

### 프로덕션 환경
- **URL**: sungsuya.com
- **호스팅**: FastComet
- **접속**: cPanel 사용

## 🔑 로그인 정보

### 관리자 계정
- **로그인 페이지**: https://sungsuya.local/wp-login.php
- **관리자 대시보드**: https://sungsuya.local/wp-admin/
- **계정 정보**:
  - 사용자명: `dcclab2022`
  - 비밀번호: `temp123!` (임시, 모든 개발 작업 완료 후 변경 예정)
  - 권한: administrator (모든 관리 기능 접근 가능)

### 중요사항
- ✅ v1.4에서 관리자 접근 문제 완전 해결됨
- ✅ 모든 WordPress 관리 기능 정상 작동
- ⚠️ 현재 임시 비밀번호는 협업 효율성을 위한 설정
- 🔒 모든 AI 개발 작업 완료 후 보안을 위해 비밀번호 변경 필요

## 📁 파일 구조

### 주요 경로
```
로컬 개발:
C:\Users\tjdxo\Local Sites\sungsuya\
├── app\public\                          # 워드프레스 루트
│   ├── wp-content\themes\sungsuya-v2-theme\  # 커스텀 테마
│   └── wp-config.php                    # 설정 파일 (v1.4 수정 완료)

백업/문서:
C:\Project\sungsuya\
├── wordpress\                           # 원본 사이트 파일
├── database\                           # SQL 백업 파일
└── docs\                               # 프로젝트 문서 (개발 보고서들)
```

### 테마 파일 구조 (v1.1에서 42% 정리 완료)
```
sungsuya-v2-theme/ (52개 파일로 최적화됨)
├── style.css                           # 워드프레스 테마 인식용
├── functions.php                       # 테마 기능 (v2.1.0)
├── front-page.php                      # 메인페이지 (v1.0에서 필터링 완성)
├── header.php, footer.php              # 헤더/푸터
├── single-places.php                   # 장소 상세페이지
├── archive-popup-store.php             # 팝업스토어 목록
├── page-tour-planner-pwa.php          # 투어플래너 PWA
├── assets/                             # CSS, JS, 이미지, 폰트
├── template-parts/                     # 템플릿 부분들
├── pwa/                               # PWA 관련 파일
└── inc/                               # PHP 모듈들 (80+ 파일)
```

## 🗄️ 데이터베이스 (v1.3에서 완전 정리 완료)

### 로컬 환경
- **데이터베이스명**: local
- **사용자명**: root
- **비밀번호**: root
- **호스트**: localhost
- **테이블 접두사**: `qndpbnfv_` (v1.3에서 통일 완료)

### 주요 테이블 (18개로 정리됨, 62% 감소)
```
핵심 WordPress 테이블:
├── qndpbnfv_posts (게시물/페이지) - 189개 레코드
├── qndpbnfv_postmeta (게시물 메타데이터) - 2,466개 레코드
├── qndpbnfv_options (사이트 설정) - 216개 레코드
├── qndpbnfv_users (사용자) - 2개 레코드
├── qndpbnfv_usermeta (사용자 메타데이터) - 33개 레코드
├── qndpbnfv_terms (택소노미 용어) - 24개 레코드
├── qndpbnfv_term_relationships (게시물-택소노미 관계) - 36개 레코드
└── qndpbnfv_term_taxonomy (택소노미 정의) - 24개 레코드
```

## ⚙️ 현재 시스템 설정

### wp-config.php (v1.4에서 완전 수정됨)
```php
// 테이블 접두사
$table_prefix = 'qndpbnfv_';

// URL 설정 (올바른 순서로 정의됨)
define('WP_HOME','https://sungsuya.local');
define('WP_SITEURL','https://sungsuya.local');

// 디버그 모드 (개발환경)
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// 멀티사이트 비활성화 (v1.4에서 수정됨)
// define('WP_ALLOW_MULTISITE', true); // 주석처리됨
```

### 시스템 상태 (현재)
- ✅ **URL 설정**: DB와 wp-config.php 완전 통일
- ✅ **사용자 권한**: 모든 관리자 권한 정상 작동
- ✅ **테이블 구조**: 깔끔하고 안정적인 18개 테이블
- ✅ **파일 구조**: 52개 파일로 최적화된 개발 환경

## 🎨 디자인 시스템

### 브랜딩
- **브랜드명**: "성수야!"
- **로고**: 성수야! | SUNGSUYA
- **컨셉**: 서울/성수동 팝업스토어 가이드

### 디자인 토큰
```css
/* 그라디언트 브랜딩 */
Primary: #667eea → #764ba2
Secondary: #f093fb → #f5576c  
Accent: #4facfe → #00f2fe

/* 반응형 브레이크포인트 */
Mobile: ~640px
Tablet: 640px-1024px
Desktop: 1024px+
```

### 기술 스택
- **폰트**: Pretendard 기본, "SEOUL GUIDE" 영문 타이틀
- **다크모드**: prefers-color-scheme 및 .dark 클래스 지원
- **CSS**: 완전한 변수 시스템 구축
- **PWA**: Service Worker (sw-v3.1.0.js)

## 📋 주요 기능 현황

### 커스텀 포스트 타입
- **places**: 장소 정보 (35개, 좌표 생성 완료)
  - 메타: place_type으로 popup_store 구분
- **popup_store**: 팝업스토어 정보 (중복 시스템 - 정리 예정)
- **tour**: 투어 정보

### 페이지 템플릿
- `front-page.php`: 메인페이지 (v1.0에서 필터링 시스템 완성)
- `single-places.php`: 장소 상세
- `archive-popup-store.php`: 팝업스토어 목록
- `page-tour-planner-pwa.php`: PWA 투어플래너
- `page-about-us.php`: 소개페이지
- `page-contact.php`: 연락처 페이지

### 현재 작동 중인 시스템
```
✅ 필터링 시스템: 전체/카페/레스토랑/팝업스토어 모든 탭 정상 작동
✅ 메타데이터 처리: 팝업스토어/맛집 정확한 분류 (v1.0 완성)
✅ 지도 생성 시스템: 모든 Places 좌표 생성 완료
✅ 크롤링 시스템: 네이버, 카카오, Instagram 등 다중 시스템
✅ 이미지 처리: 통합 이미지 크롤링 및 썸네일 관리
✅ PWA 기능: 오프라인 지원, 모바일 최적화
```

## 🚀 개발 워크플로우

### 로컬 개발 (권장 순서)
1. **Local WP에서 사이트 시작**
2. **관리자 로그인** (https://sungsuya.local/wp-admin/)
3. **VS Code로 테마 파일 편집**
4. **브라우저에서 실시간 확인**
5. **관리자 대시보드에서 기능 테스트** ← v1.4부터 가능!
6. **변경사항 백업**

### 테스트 방법
```bash
# 메인페이지 필터링 테스트
1. https://sungsuya.local 접속
2. "팝업스토어" 탭 클릭 → 4개 팝업스토어만 표시되는지 확인
3. "레스토랑" 탭 클릭 → 4개 맛집만 표시되는지 확인
4. "전체" 탭 클릭 → 8개 모든 장소 표시되는지 확인

# 팝업스토어 전용 페이지 테스트
1. 메뉴에서 "팝업스토어" 클릭 → https://sungsuya.local/popup-stores
2. 12개+ 팝업스토어가 표시되는지 확인
3. 날짜 표시 문제 확인 → 모든 팝업이 "1.1 - 1.1"로 동일 표시 (수정 필요)
4. 페이지네이션 확인 → "2페이지"로 더 많은 팝업스토어 있음

# 관리자 기능 테스트  
1. wp-admin 접속 → 모든 메뉴 정상 표시 확인
2. 성수야! 관리 → 대시보드, 크롤링 시스템 확인
3. 장소 관리 → 35개 Places 데이터 확인
```

### 디버깅
```bash
# 디버그 로그 확인
C:\Users\tjdxo\Local Sites\sungsuya\app\public\wp-content\debug.log

# Local WP 재시작 (문제 발생 시)
Local WP > Stop site > Start site (3-5초 대기)
```

## 📊 현재 프로젝트 상태

### 완료된 주요 작업 (v1.0 ~ v1.4)
```
v1.0: ✅ 메타데이터 구조 통일 및 필터링 기능 완성
v1.1: ✅ 파일 구조 정리 (90개 → 52개 파일, 42% 감소)
v1.2: ✅ 종합 문제점 분석 및 우선순위 계획 수립
v1.3: ✅ DB 구조 완전 정리 (47개 → 18개 테이블, 62% 감소)
v1.4: ✅ WordPress 관리자 접근 문제 완전 해결
```

### 현재 시스템 지표
- **Places 현황**: 35개 (좌표 생성 100% 완료)
- **팝업스토어**: 12개+ 등록됨 (메인페이지에는 최근 4개만 표시)
- **필터링 정확도**: 100% (모든 탭 정상 작동)
- **관리자 접근성**: 100% (모든 기능 접근 가능)
- **시스템 안정성**: 완전 안정 (재시작 후에도 지속)

### 다음 우선순위 작업 (Critical Priority)
```
🗓️ 1. 팝업스토어 날짜 표시 오류 해결
   → 모든 팝업스토어가 "1.1 - 1.1"로 동일 표시되는 문제
   → 실제 운영 기간이 제대로 표시되지 않아 사용자 혼동 야기

📝 2. 게시글 등록 시스템 중복 제거  
   → place-metabox-manager.php vs place-metabox-manager-fixed.php 통합

📊 3. 실시간 데이터 수집 시스템 구축
   → 2025년 774개 신규 팝업스토어 데이터 수집 (경쟁력 확보)
```

## 🤝 AI 개발자 협업 가이드

### 작업 시작 전 체크리스트
- [ ] 관리자 로그인 테스트 (dcclab2022/temp123!)
- [ ] 현재 프로젝트 상태 파악 (이 가이드 + 최신 보고서)
- [ ] 작업할 파일 백업 생성
- [ ] 테스트 환경 확인 (https://sungsuya.local)

### 권장 작업 순서
1. **문제 분석**: 관리자 대시보드에서 현재 상태 확인
2. **코드 수정**: VS Code에서 파일 편집
3. **실시간 테스트**: 브라우저에서 즉시 확인
4. **관리자 검증**: wp-admin에서 변경사항 확인
5. **최종 테스트**: 전체 기능 동작 확인

### 파일 수정 시 주의사항
- **백업 필수**: 중요 파일 수정 전 반드시 백업
- **날짜 포함 명명**: `filename-backup-YYYYMMDD.php` 형식 사용
- **경로 정확히**: 절대 경로 사용으로 혼동 방지
- **단계별 테스트**: 작은 변경사항마다 즉시 테스트

## 🔧 문제 해결 가이드

### 자주 발생하는 문제들

#### 관리자 접근 문제 (v1.4에서 해결됨)
```
증상: 로그인 후 wp-admin 접근 시 500 에러
해결: v1.4에서 완전 해결됨 (URL 설정 통일, 권한 복구)
예방: Local WP 재시작 후에도 정상 작동 확인됨
```

#### 필터링 작동 안함 (v1.0에서 해결됨)
```
증상: 팝업스토어 필터 클릭해도 모든 장소 표시
해결: v1.0에서 메타데이터 처리 방식 통일로 해결
확인: 현재 모든 필터 100% 정상 작동
```

#### 일반적인 해결 방법
```bash
1. 사이트 로딩 안됨 → Local WP 재시작
2. 스타일 적용 안됨 → 브라우저 캐시 삭제  
3. DB 연결 오류 → wp-config.php 설정 확인
4. 권한 오류 → 관리자 대시보드에서 사용자 관리 확인
```

## 📞 협업 연락처 및 자료

### 개발 문서 위치
```
C:\Project\sungsuya\docs\
├── sungsuya-guide-v1.0.md             # 원본 가이드 (프로젝트 초기)
├── sungsuya-guide-v1.5.md             # 현재 최신 가이드 (이 문서)
├── sungsuya-report-v1.0.md ~ v1.4.md  # 상세 개발 보고서들
├── sungsuya-manual-reports.md         # 보고서 작성 지침
└── sungsuya-manual-guides.md          # 가이드 작성 요령
```

### 백업 위치
```
주요 백업 경로:
├── FastComet 실서버 (최종 안전 백업)
├── C:\Project\sungsuya\ (프로젝트 원본 백업)
└── C:\Project\sungsuya\backup-before-cleanup\ (버전별 백업)
```

### 주요 명령어
```bash
# Local WP 관리
Local WP > Stop/Start site

# 로그 확인  
wp-content/debug.log

# 관리자 접근
https://sungsuya.local/wp-admin/
```

---

**최종 업데이트**: 2025년 8월 5일 (v1.4 - 관리자 접근 문제 해결)  
**현재 상태**: 완전한 개발 환경 구축 완료, Critical Priority 작업 준비 완료  
**다음 작업**: 팝업스토어 날짜 표시 문제 해결 (추천 시작점)