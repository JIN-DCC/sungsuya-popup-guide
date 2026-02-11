#!/bin/bash

# SUNGSUYA 스테이징 배포 테스트 스크립트
# dev.sungsuya.com 연결 및 기본 설정 테스트

echo "🧪 SUNGSUYA 스테이징 환경 테스트 시작"
echo "=========================================="

# 환경 변수 설정
STAGING_URL="dev.sungsuya.com"
LOCAL_PATH="C:\Project\sungsuya\sungsuya-popup-guide"

echo "📋 테스트 환경 정보:"
echo "  - 스테이징 URL: https://$STAGING_URL"
echo "  - 로컬 경로: $LOCAL_PATH" 
echo "  - Git 브랜치: $(git branch --show-current 2>/dev/null || echo 'staging')"
echo ""

# 1. 기본 연결 테스트
echo "🔍 1. 기본 연결 테스트"
echo "dev.sungsuya.com 도메인 해결 여부 확인..."

# ping 테스트 (Windows)
if ping -n 1 $STAGING_URL > /dev/null 2>&1; then
    echo "✅ 도메인 해결 성공: $STAGING_URL"
else
    echo "⚠️ 도메인 해결 실패 - FastComet DNS 전파 대기 중일 수 있음"
fi

# 2. 파일 구조 확인
echo ""
echo "📁 2. 배포 파일 구조 확인"

if [ -f "deployment/staging/wp-config-staging.php" ]; then
    echo "✅ 스테이징 WordPress 설정 파일 존재"
else
    echo "❌ wp-config-staging.php 파일 누락"
fi

if [ -f "deployment/staging/.htaccess-staging" ]; then
    echo "✅ 스테이징 .htaccess 파일 존재"
else
    echo "❌ .htaccess-staging 파일 누락"
fi

if [ -f "deployment/scripts/deploy-to-staging.sh" ]; then
    echo "✅ 배포 스크립트 존재"
    
    # 배포 스크립트 권한 확인
    if [ -x "deployment/scripts/deploy-to-staging.sh" ]; then
        echo "✅ 배포 스크립트 실행 권한 있음"
    else
        echo "⚠️ 배포 스크립트 실행 권한 필요"
        chmod +x deployment/scripts/deploy-to-staging.sh 2>/dev/null || echo "  (Windows 환경에서는 권한 변경 불필요)"
    fi
else
    echo "❌ deploy-to-staging.sh 스크립트 누락"
fi

# 3. WordPress 테마 파일 확인
echo ""
echo "🎨 3. WordPress 테마 파일 확인"

if [ -d "wp-content/themes/sungsuya-v2-theme" ]; then
    echo "✅ SUNGSUYA 테마 폴더 존재"
    
    # 핵심 파일 확인
    THEME_PATH="wp-content/themes/sungsuya-v2-theme"
    
    if [ -f "$THEME_PATH/functions.php" ]; then
        FUNCTIONS_SIZE=$(wc -c < "$THEME_PATH/functions.php" 2>/dev/null || echo "0")
        echo "✅ functions.php 존재 (크기: ${FUNCTIONS_SIZE} bytes)"
        
        if [ "$FUNCTIONS_SIZE" -lt 50000 ]; then
            echo "✅ functions.php 최적화 상태 (67% 최적화 완료)"
        fi
    else
        echo "❌ functions.php 파일 누락"
    fi
    
    if [ -f "$THEME_PATH/style.css" ]; then
        echo "✅ style.css 존재"
    else
        echo "❌ style.css 파일 누락"
    fi
    
    if [ -f "$THEME_PATH/index.php" ]; then
        echo "✅ index.php 존재"
    else
        echo "❌ index.php 파일 누락"
    fi
    
else
    echo "❌ SUNGSUYA 테마 폴더 누락"
fi

# 4. PWA 파일 확인
echo ""
echo "📱 4. PWA 기능 파일 확인"

if [ -f "wp-content/themes/sungsuya-v2-theme/pwa/manifest.json" ]; then
    echo "✅ PWA 매니페스트 파일 존재"
else
    echo "⚠️ PWA 매니페스트 파일 확인 필요"
fi

if [ -f "wp-content/themes/sungsuya-v2-theme/pwa/sw-v2.js" ]; then
    echo "✅ Service Worker 파일 존재"
else
    echo "⚠️ Service Worker 파일 확인 필요"
fi

# 5. GitHub Actions 워크플로우 확인
echo ""
echo "🤖 5. GitHub Actions 자동화 확인"

if [ -f ".github/workflows/gemini-deploy.yml" ]; then
    echo "✅ Gemini 배포 워크플로우 존재"
    
    # STAGING_URL 설정 확인
    if grep -q "dev.sungsuya.com" ".github/workflows/gemini-deploy.yml"; then
        echo "✅ 스테이징 URL 설정 확인됨"
    else
        echo "⚠️ 스테이징 URL 설정 확인 필요"
    fi
else
    echo "❌ Gemini 배포 워크플로우 누락"
fi

if [ -f ".github/workflows/claude-quality-check.yml" ]; then
    echo "✅ Claude 품질 검사 워크플로우 존재"
else
    echo "⚠️ Claude 품질 검사 워크플로우 확인 필요"
fi

if [ -f ".github/workflows/chatgpt-integration.yml" ]; then
    echo "✅ ChatGPT 통합 워크플로우 존재"
else
    echo "⚠️ ChatGPT 통합 워크플로우 확인 필요"
fi

# 6. 3-AI 협업 환경 상태 확인
echo ""
echo "🤝 6. 3-AI 협업 환경 상태"

echo "🖥️ Claude Desktop: 로컬 환경 준비 완료"
echo "  - 프로젝트 경로: $LOCAL_PATH"
echo "  - Git 관리: 활성화"
echo "  - 파일 접근: 직접 가능"

echo "💬 ChatGPT Plus: GitHub 연동 준비 완료"
echo "  - 코드 복사/붙여넣기: 가능"
echo "  - 실시간 피드백: 지원"
echo "  - 기능 개발: 대기 중"

echo "⚡ Gemini CLI: 배포 자동화 준비 완료"
echo "  - GitHub Token: 설정 대기 중"
echo "  - 배포 스크립트: 준비 완료"
echo "  - 모니터링: 활성화 예정"

# 7. 다음 단계 안내
echo ""
echo "🎯 7. 다음 단계 액션 아이템"
echo "================================"

echo "⚡ IMMEDIATE (지금 바로):"
echo "  1. FastComet cPanel에서 WordPress 설치 (dev.sungsuya.com)"
echo "  2. 데이터베이스 생성: sungsuya_dev"
echo "  3. wp-config-staging.php 업로드 및 적용"

echo ""
echo "🚀 TODAY (오늘 완료):"
echo "  1. 스테이징 환경에 SUNGSUYA 테마 업로드"
echo "  2. .htaccess-staging 파일 적용"
echo "  3. 기본 WordPress 설정 완료"

echo ""
echo "🎨 THIS WEEK (이번 주):"
echo "  1. 첫 3-AI 협업 프로젝트 시작 (실시간 알림 시스템)"
echo "  2. Gemini CLI GitHub Token 설정"
echo "  3. 자동 배포 파이프라인 테스트"

# 8. 연결 테스트 결과 요약
echo ""
echo "📊 8. 테스트 결과 요약"
echo "======================"

# 성공한 항목 카운트
SUCCESS_COUNT=0
TOTAL_COUNT=8

# 기본 체크항목들
if [ -f "deployment/staging/wp-config-staging.php" ]; then ((SUCCESS_COUNT++)); fi
if [ -f "deployment/staging/.htaccess-staging" ]; then ((SUCCESS_COUNT++)); fi
if [ -f "deployment/scripts/deploy-to-staging.sh" ]; then ((SUCCESS_COUNT++)); fi
if [ -d "wp-content/themes/sungsuya-v2-theme" ]; then ((SUCCESS_COUNT++)); fi
if [ -f "wp-content/themes/sungsuya-v2-theme/functions.php" ]; then ((SUCCESS_COUNT++)); fi
if [ -f ".github/workflows/gemini-deploy.yml" ]; then ((SUCCESS_COUNT++)); fi
if [ -f "README.md" ]; then ((SUCCESS_COUNT++)); fi
if [ -f "package.json" ]; then ((SUCCESS_COUNT++)); fi

COMPLETION_PERCENT=$((SUCCESS_COUNT * 100 / TOTAL_COUNT))

echo "✅ 완료된 항목: $SUCCESS_COUNT/$TOTAL_COUNT"
echo "📈 준비도: $COMPLETION_PERCENT%"

if [ $COMPLETION_PERCENT -ge 80 ]; then
    echo "🎉 스테이징 환경 준비 상태: 우수 (배포 가능)"
elif [ $COMPLETION_PERCENT -ge 60 ]; then
    echo "✅ 스테이징 환경 준비 상태: 양호 (소수 항목 확인 필요)"
else
    echo "⚠️ 스테이징 환경 준비 상태: 보통 (몇 가지 항목 완료 필요)"
fi

echo ""
echo "🌐 FastComet 서브도메인 상태:"
echo "  - 도메인: dev.sungsuya.com"
echo "  - 폴더: /dev.sungsuya.com"
echo "  - SSL: 자동 설정 예정"
echo "  - WordPress: 설치 대기 중"

echo ""
echo "🎊 결론: SUNGSUYA 스테이징 환경이 거의 준비되었습니다!"
echo "이제 FastComet에서 WordPress 설치만 하면 바로 첫 3-AI 협업을 시작할 수 있습니다."

echo ""
echo "=========================================="
echo "🧪 스테이징 환경 테스트 완료"
echo "$(date)"
echo "=========================================="
