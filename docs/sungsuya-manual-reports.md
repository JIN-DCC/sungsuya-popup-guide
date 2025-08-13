# SUNGSUYA 프로젝트 개발 보고서 작성 지침 v1.1

**문서 목적**: AI 개발 도우미들 간의 완벽한 연속성과 협업 효율성 확보  
**적용 대상**: Claude AI Development Team  
**최종 업데이트**: 2025년 8월 5일  

---

## 🎯 보고서 작성 목적

### 핵심 목표
1. **완벽한 연속성**: 다른 AI가 보고서만 읽고도 즉시 작업 상황을 100% 파악
2. **제로 컨텍스트 로스**: 이전 작업 내용의 손실 없이 완벽한 인수인계
3. **즉시 작업 가능**: 별도 질문이나 확인 없이 바로 개발 작업 시작 가능
4. **품질 향상**: 보고서를 통한 지속적인 코드 품질 및 프로세스 개선

---

## 📋 파일 명명 규칙 (2025년 8월 업데이트)

### 보고서 파일명 체계
```
sungsuya-report-v[X.Y].md

예시:
- sungsuya-report-v1.0.md  (메타데이터 구조 통일)
- sungsuya-report-v1.1.md  (파일 구조 정리)  
- sungsuya-report-v1.2.md  (종합 문제점 분석)
- sungsuya-report-v1.3.md  (DB 구조 정리)
- sungsuya-report-v1.4.md  (관리자 접근 해결)
- sungsuya-report-v1.5.md  (다음 보고서)
```

### 버전 번호 체계
- **Major.Minor** (예: v1.0, v1.1, v2.0)
- **Major**: 큰 기능 추가, 아키텍처 변경, 중대한 문제 해결
- **Minor**: 작은 기능 개선, 버그 수정, 코드 리팩토링

### 연관 문서 체계
```
프로젝트 가이드:
- sungsuya-guide-v1.0.md   (원본 가이드)
- sungsuya-guide-v1.5.md   (현재 최신 가이드)

개발 보고서:  
- sungsuya-report-v1.0.md ~ v1.4.md (완료된 작업들)

작업 매뉴얼:
- sungsuya-manual-reports.md  (이 문서)
- sungsuya-manual-guides.md   (가이드 작성 요령)
```

---

## 📋 보고서 구조 (필수 섹션)

### 1. 헤더 정보 (Header Information)
```markdown
# SUNGSUYA 프로젝트 개발 보고서 v[X.Y]

**프로젝트**: sungsuya.com (성수동 팝업스토어 가이드 PWA)  
**보고서 작성일**: YYYY년 MM월 DD일  
**작업 참여**: AI Development Team + 인간 개발자 협업  
**버전**: v[X.Y] - [주요 작업 내용 요약]  
**이전 버전**: v[X.Y-1] (연결성 명시)
```

### 11. 연관 문서 및 참조 (필수!)
```markdown
## 🔗 연관 문서 및 참조

### 프로젝트 문서
- **sungsuya-guide-v1.5.md** - 현재 최신 프로젝트 가이드
- **sungsuya-guide-v1.0.md** - 원본 가이드 (참고용)
- **sungsuya-report-v[이전버전].md** - 이전 보고서
- **sungsuya-manual-reports.md** - 보고서 작성 지침
- **sungsuya-manual-guides.md** - 가이드 작성 요령

### 개발 환경
- **로컬**: https://sungsuya.local (Local WP)
- **프로덕션**: sungsuya.com (FastComet)
- **관리자**: https://sungsuya.local/wp-admin

### 현재 시스템 설정
```php
// wp-config.php (현재 상태)
$table_prefix = 'qndpbnfv_';
define('WP_HOME','https://sungsuya.local');
define('WP_SITEURL','https://sungsuya.local');
```
```

### 품질 체크리스트에 추가
#### ✅ 파일명 및 참조 정확성 (2025년 8월 추가)
- [ ] 파일명이 sungsuya-report-v[X.Y].md 형식을 따르는가?
- [ ] 연관 문서 섹션의 파일명들이 실제 파일명과 일치하는가?
- [ ] 다른 문서 참조 시 정확한 버전을 명시했는가?

### 버전 연결성
```markdown
**이전 버전**: sungsuya-report-v1.3.md - DB 구조 완전 정리
**현재 버전**: sungsuya-report-v1.4.md - 관리자 접근 문제 해결
**다음 계획**: sungsuya-report-v1.5.md - 팝업스토어 날짜 표시 수정 예정
```

### 가이드와의 연계
```markdown
관련 가이드 업데이트:
- sungsuya-guide-v1.5.md ← 이 보고서(v1.4)까지의 내용 반영됨
- sungsuya-guide-v1.6.md ← 다음 보고서(v1.5) 완료 후 업데이트 예정
```

---

**지침서 작성자**: AI Development Team  
**승인자**: 인간 개발자  
**다음 리뷰**: 보고서 v2.0 작성 시 지침서도 함께 업데이트  

> 이 지침서는 SUNGSUYA 프로젝트의 개발 품질과 연속성을 보장하기 위한 핵심 문서입니다. 모든 AI 개발 도우미는 보고서 작성 전 반드시 이 지침을 숙지하고 따라야 합니다.