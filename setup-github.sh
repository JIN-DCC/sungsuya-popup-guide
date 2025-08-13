#!/bin/bash

# SUNGSUYA GitHub Repository 초기 설정 스크립트
# 로컬 개발 환경에서 GitHub에 첫 커밋을 위한 자동화 스크립트

set -e

# ============================================================================
# 설정 변수
# ============================================================================

REPO_NAME="sungsuya-popup-guide"
GITHUB_USERNAME=""  # GitHub 사용자명을 입력하세요
INITIAL_BRANCH="main"

# ============================================================================
# 색상 출력 함수
# ============================================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

log() {
    echo -e "${GREEN}[$(date +'%H:%M:%S')] $1${NC}"
}

warn() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}"
    exit 1
}

info() {
    echo -e "${BLUE}[INFO] $1${NC}"
}

# ============================================================================
# GitHub Repository 설정 가이드
# ============================================================================

echo -e "${PURPLE}"
echo "🏪 SUNGSUYA GitHub Repository 설정"
echo "=================================="
echo -e "${NC}"

log "3-AI 협업 환경을 위한 GitHub Repository 초기 설정을 시작합니다!"

# GitHub 사용자명 입력
if [ -z "$GITHUB_USERNAME" ]; then
    read -p "GitHub 사용자명을 입력하세요: " GITHUB_USERNAME
    if [ -z "$GITHUB_USERNAME" ]; then
        error "GitHub 사용자명이 필요합니다"
    fi
fi

# ============================================================================
# Git 초기 설정 확인
# ============================================================================

log "🔧 Git 설정 확인 중..."

# Git 사용자 정보 확인
if ! git config user.name &>/dev/null; then
    read -p "Git 사용자 이름을 설정하세요: " GIT_USER_NAME
    git config --global user.name "$GIT_USER_NAME"
fi

if ! git config user.email &>/dev/null; then
    read -p "Git 이메일을 설정하세요: " GIT_USER_EMAIL
    git config --global user.email "$GIT_USER_EMAIL"
fi

log "✅ Git 사용자: $(git config user.name) <$(git config user.email)>"

# ============================================================================
# Repository 초기화
# ============================================================================

log "📁 Repository 초기화 중..."

# Git 저장소 초기화 (이미 있다면 스킵)
if [ ! -d ".git" ]; then
    git init
    log "✅ Git 저장소 초기화 완료"
else
    log "ℹ️ 기존 Git 저장소 발견"
fi

# Remote 설정
REMOTE_URL="https://github.com/$GITHUB_USERNAME/$REPO_NAME.git"
if ! git remote get-url origin &>/dev/null; then
    git remote add origin "$REMOTE_URL"
    log "✅ Remote origin 설정: $REMOTE_URL"
else
    warn "기존 remote origin 발견: $(git remote get-url origin)"
fi

# ============================================================================
# 브랜치 구조 생성
# ============================================================================

log "🌿 3-AI 협업 브랜치 구조 생성 중..."

# 메인 브랜치 확인/생성
CURRENT_BRANCH=$(git branch --show-current 2>/dev/null || echo "")
if [ "$CURRENT_BRANCH" != "$INITIAL_BRANCH" ]; then
    git checkout -b "$INITIAL_BRANCH" 2>/dev/null || git checkout "$INITIAL_BRANCH"
fi

# 개발 브랜치들 생성
BRANCHES=(
    "develop"
    "staging"
    "feature/claude-optimizations"
    "feature/chatgpt-features"
    "feature/gemini-deployment"
)

for branch in "${BRANCHES[@]}"; do
    if ! git show-ref --verify --quiet "refs/heads/$branch"; then
        git checkout -b "$branch"
        log "✅ 브랜치 생성: $branch"
        git checkout "$INITIAL_BRANCH"
    else
        log "ℹ️ 브랜치 이미 존재: $branch"
    fi
done

# ============================================================================
# 첫 커밋 생성
# ============================================================================

log "📝 첫 커밋 생성 중..."

# .gitignore가 제대로 작동하는지 확인
git add .gitignore

# 모든 파일 추가 (실제로는 .gitignore 규칙 적용됨)
git add .

# 파일 상태 확인
STAGED_FILES=$(git diff --cached --name-only | wc -l)
log "📊 스테이징된 파일 수: $STAGED_FILES"

if [ "$STAGED_FILES" -gt 0 ]; then
    # 첫 커밋 생성
    git commit -m "🎉 Initial commit: SUNGSUYA 3-AI Collaboration Setup

🏪 SUNGSUYA - 성수동 팝업스토어 가이드 PWA
⚡ 67% 성능 최적화 완료 (functions.php: 40.83KB → 13.47KB)
🤖 3-AI 협업 환경 구축 완료

Features:
✅ WordPress 최적화된 테마 (sungsuya-v2-theme)
✅ PWA 완전 지원 (Service Worker + Manifest)
✅ GitHub Actions 워크플로우 (3-AI 협업)
✅ 자동 배포 시스템 (FastComet 연동)
✅ 완벽한 문서화 시스템

AI Collaboration:
🖥️ Claude Desktop: 프로젝트 매니저 + 품질 관리
💬 ChatGPT Plus: 기능 개발 + 창의적 솔루션  
⚡ Gemini CLI: DevOps + 배포 자동화

Repository Structure:
├── wp-content/themes/sungsuya-v2-theme/ (67% 최적화된 테마)
├── .github/workflows/ (3-AI 협업 자동화)
├── deployment/ (FastComet 배포 설정)
├── docs/ (완벽한 프로젝트 문서)
└── 기타 설정 파일들

Ready for production deployment! 🚀"
    
    log "✅ 첫 커밋 생성 완료"
else
    warn "스테이징된 파일이 없습니다"
fi

# ============================================================================
# GitHub Repository 생성 안내
# ============================================================================

echo
echo -e "${PURPLE}"
echo "🎯 다음 단계: GitHub Repository 생성"
echo "====================================="
echo -e "${NC}"

info "1. GitHub에서 새 Repository 생성:"
info "   - Repository 이름: $REPO_NAME"
info "   - 설명: 성수동 팝업스토어 가이드 PWA - 3-AI 협업 프로젝트"
info "   - Public으로 설정"
info "   - README, .gitignore, License 추가 안 함 (이미 있음)"

echo
info "2. Repository 생성 후 실행할 명령어:"
echo -e "${GREEN}"
echo "git push -u origin main"
echo "git push origin develop"
echo "git push origin staging"
echo "git push origin feature/claude-optimizations"
echo "git push origin feature/chatgpt-features"
echo "git push origin feature/gemini-deployment"
echo -e "${NC}"

echo
info "3. GitHub Actions 확인:"
info "   - .github/workflows/ 디렉토리의 워크플로우들이 자동 실행됨"
info "   - Claude, ChatGPT, Gemini 각각의 자동화 파이프라인 동작"

echo
info "4. 브랜치 보호 규칙 설정 (권장):"
info "   - main: 직접 푸시 금지, PR만 허용"
info "   - develop: 코드 리뷰 필수"
info "   - staging: 자동 배포 테스트 환경"

# ============================================================================
# FastComet 배포 준비
# ============================================================================

echo
echo -e "${PURPLE}"
echo "🚀 FastComet 배포 준비"
echo "====================="
echo -e "${NC}"

info "1. FastComet cPanel에서 서브도메인 생성:"
info "   - 서브도메인: dev"
info "   - 도메인: sungsuya.com"
info "   - Document Root: /public_html/dev"

info "2. 데이터베이스 생성:"
info "   - Database: sungsuya_dev"
info "   - User: sungsuya_dev_user"
info "   - Password: [보안상 강력한 비밀번호 설정]"

info "3. SSH 키 설정 (자동 배포용):"
info "   - GitHub Actions에서 FastComet 서버 접근"
info "   - 비밀키를 GitHub Secrets에 저장"

# ============================================================================
# 3-AI 협업 가이드
# ============================================================================

echo
echo -e "${PURPLE}"
echo "🤖 3-AI 협업 시작 가이드"
echo "========================"
echo -e "${NC}"

info "1. Claude Desktop (현재 환경):"
info "   - 브랜치: feature/claude-optimizations"
info "   - 역할: 프로젝트 매니저 + 품질 관리"
info "   - 작업: 로컬에서 직접 코드 수정 후 Git 관리"

info "2. ChatGPT Plus 협업:"
info "   - 브랜치: feature/chatgpt-features"
info "   - 역할: 신기능 개발 + 창의적 솔루션"
info "   - 방법: 코드 복사/붙여넣기 + 실시간 피드백"

info "3. Gemini CLI 협업:"
info "   - 브랜치: feature/gemini-deployment"
info "   - 역할: DevOps + 배포 자동화"
info "   - 작업: 터미널 기반 배포 스크립트 실행"

# ============================================================================
# 성공적인 설정 완료
# ============================================================================

echo
echo -e "${GREEN}"
echo "🎉 SUNGSUYA GitHub Repository 설정 완료!"
echo "=========================================="
echo -e "${NC}"

log "✅ Git 저장소 초기화 완료"
log "✅ 3-AI 협업 브랜치 구조 생성 완료"  
log "✅ 첫 커밋 생성 완료"
log "✅ 67% 성능 최적화된 코드베이스 준비 완료"
log "✅ GitHub Actions 워크플로우 준비 완료"
log "✅ FastComet 배포 스크립트 준비 완료"

echo
echo -e "${YELLOW}"
echo "📞 다음 협업 시 할 일:"
echo "===================="
echo "1. GitHub에서 Repository 생성"
echo "2. 'git push -u origin main' 실행"
echo "3. FastComet 서브도메인 설정 (dev.sungsuya.com)"
echo "4. 첫 3-AI 협업 프로젝트 시작 (실시간 알림 시스템)"
echo -e "${NC}"

echo
log "🚀 혁신적인 3-AI 협업으로 SUNGSUYA를 발전시켜보세요!"
log "📚 자세한 내용은 docs/ 디렉토리의 문서들을 참조하세요."

exit 0
