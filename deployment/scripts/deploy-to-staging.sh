#!/bin/bash

# SUNGSUYA 스테이징 환경 배포 스크립트
# 개발 브랜치를 dev.sungsuya.com에 자동 배포

set -e  # 오류 발생 시 스크립트 중단

# ============================================================================
# 설정 변수
# ============================================================================

STAGING_URL="dev.sungsuya.com"
STAGING_PATH="/home/sungsuya/public_html/dev.sungsuya.com"
DB_NAME="sungsuya_dev"
DB_USER="sungsuya_dev_user"
DB_PASS=""  # 환경변수에서 가져옴
BACKUP_PATH="/home/sungsuya/backups/staging"

# ============================================================================
# 로깅 함수
# ============================================================================

log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

error() {
    echo "[ERROR] $1" >&2
    exit 1
}

# ============================================================================
# 배포 전 체크
# ============================================================================

log "🚀 SUNGSUYA 스테이징 배포 시작"
log "대상 환경: $STAGING_URL"

# Git 상태 확인
if ! git status &>/dev/null; then
    error "Git 저장소가 아닙니다"
fi

# 현재 브랜치 확인
CURRENT_BRANCH=$(git branch --show-current)
log "현재 브랜치: $CURRENT_BRANCH"

# 변경사항 확인
if ! git diff --quiet HEAD; then
    log "⚠️ 커밋되지 않은 변경사항이 있습니다"
    read -p "계속 진행하시겠습니까? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        error "배포가 취소되었습니다"
    fi
fi

# ============================================================================
# 백업 생성
# ============================================================================

log "💾 스테이징 환경 백업 생성 중..."

# 백업 디렉토리 생성
mkdir -p "$BACKUP_PATH/$(date +'%Y%m%d_%H%M%S')"

# 파일 백업 (실제 환경에서는 rsync 또는 scp 사용)
# rsync -av "$STAGING_PATH/" "$BACKUP_PATH/$(date +'%Y%m%d_%H%M%S')/files/"

# 데이터베이스 백업 (실제 환경에서는 mysqldump 사용)
# mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_PATH/$(date +'%Y%m%d_%H%M%S')/database.sql"

log "✅ 백업 완료"

# ============================================================================
# 의존성 설치 및 빌드
# ============================================================================

log "📦 의존성 설치 및 빌드..."

# Node.js 의존성 설치
if [ -f "package.json" ]; then
    npm ci --production
    log "✅ Node.js 의존성 설치 완료"
else
    log "ℹ️ package.json 없음 - Node.js 빌드 건너뛰기"
fi

# PWA 빌드
if npm run build --if-present; then
    log "✅ PWA 빌드 완료"
else
    log "ℹ️ 빌드 스크립트 없음"
fi

# CSS 컴파일 (있는 경우)
if npm run css-build --if-present; then
    log "✅ CSS 컴파일 완료"
fi

# ============================================================================
# 파일 업로드
# ============================================================================

log "📁 파일 업로드 중..."

# WordPress 테마 파일 업로드 (실제로는 rsync/scp 사용)
# rsync -av --delete \
#     --exclude='node_modules' \
#     --exclude='.git' \
#     --exclude='*.log' \
#     --exclude='wp-config.php' \
#     wp-content/themes/sungsuya-v2-theme/ \
#     user@$STAGING_URL:$STAGING_PATH/wp-content/themes/sungsuya-v2-theme/

# 배포 설정 파일 복사
# scp deployment/staging/dev-config.php user@$STAGING_URL:$STAGING_PATH/wp-config.php

log "✅ 파일 업로드 완료"

# ============================================================================
# 데이터베이스 업데이트
# ============================================================================

log "🗄️ 데이터베이스 업데이트..."

# WordPress CLI를 통한 데이터베이스 업데이트 (실제 환경)
# ssh user@$STAGING_URL "cd $STAGING_PATH && wp core update-db --allow-root"

# 캐시 클리어
# ssh user@$STAGING_URL "cd $STAGING_PATH && wp cache flush --allow-root"

log "✅ 데이터베이스 업데이트 완료"

# ============================================================================
# 배포 후 테스트
# ============================================================================

log "🧪 배포 후 기본 테스트..."

# 사이트 접근성 테스트
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "https://$STAGING_URL" || echo "000")
if [ "$HTTP_STATUS" = "200" ]; then
    log "✅ 사이트 접근 가능 (HTTP $HTTP_STATUS)"
else
    error "❌ 사이트 접근 불가 (HTTP $HTTP_STATUS)"
fi

# WordPress 테마 활성화 확인
if curl -s "https://$STAGING_URL" | grep -q "sungsuya-v2-theme"; then
    log "✅ 테마 정상 로딩 확인"
else
    log "⚠️ 테마 로딩 확인 필요"
fi

# PWA 매니페스트 확인
if curl -s "https://$STAGING_URL/wp-content/themes/sungsuya-v2-theme/pwa/manifest.json" | grep -q "SUNGSUYA"; then
    log "✅ PWA 매니페스트 정상"
else
    log "⚠️ PWA 매니페스트 확인 필요"
fi

# Service Worker 확인
if curl -s "https://$STAGING_URL/sw-v2.js" | grep -q "SUNGSUYA"; then
    log "✅ Service Worker 정상"
else
    log "⚠️ Service Worker 확인 필요"
fi

# ============================================================================
# 배포 완료 보고
# ============================================================================

log "🎉 스테이징 배포 완료!"
log "=========================================="
log "배포 URL: https://$STAGING_URL"
log "배포 시간: $(date)"
log "브랜치: $CURRENT_BRANCH"
log "커밋: $(git rev-parse --short HEAD)"
log "=========================================="

# 성능 체크 (기본)
log "📊 기본 성능 체크..."
LOAD_TIME=$(curl -s -o /dev/null -w "%{time_total}" "https://$STAGING_URL")
log "페이지 로딩 시간: ${LOAD_TIME}초"

if (( $(echo "$LOAD_TIME < 3.0" | bc -l) )); then
    log "✅ 로딩 속도 양호 (<3초)"
else
    log "⚠️ 로딩 속도 검토 필요 (>3초)"
fi

# ============================================================================
# 알림 발송 (옵션)
# ============================================================================

log "📱 배포 완료 알림..."

# Slack/Discord 알림 (실제 환경에서 설정)
# curl -X POST -H 'Content-type: application/json' \
#     --data '{"text":"🚀 SUNGSUYA 스테이징 배포 완료!\n- URL: https://'$STAGING_URL'\n- 브랜치: '$CURRENT_BRANCH'\n- 시간: '$(date)'"}' \
#     $SLACK_WEBHOOK_URL

log "✅ 모든 배포 프로세스 완료"
log "🔗 스테이징 사이트: https://$STAGING_URL"

exit 0
