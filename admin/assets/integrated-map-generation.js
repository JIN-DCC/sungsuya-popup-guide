/**
 * 🗺️ 통합 지도생성 시스템 - JavaScript (수정 버전)
 * 
 * 무한로딩 문제 해결 및 최적화
 * 
 * @version 2.0.0
 * @since 2025-06-25
 */

class IntegratedMapGeneration {
    constructor() {
        this.isProcessing = false;
        this.progressInterval = null;
        this.progressCheckCount = 0;
        this.maxProgressChecks = 60; // 최대 60회 체크 (60초)
        this.logContainer = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadInitialData();
        this.setupLogContainer();
        console.log('🗺️ 통합 지도생성 시스템 초기화 완료');
    }

    setupLogContainer() {
        this.logContainer = document.querySelector('#progress-log');
        if (this.logContainer) {
            this.logContainer.style.fontFamily = 'monospace';
            this.logContainer.style.fontSize = '12px';
            this.logContainer.style.lineHeight = '1.4';
        }
    }

    bindEvents() {
        // 일괄 처리 시작
        jQuery('#start-batch-processing').on('click', (e) => {
            e.preventDefault();
            this.startBatchProcessing();
        });

        // 데이터 새로고침
        jQuery('#refresh-data').on('click', (e) => {
            e.preventDefault();
            this.refreshData();
        });

        // 개별 액션 버튼들
        jQuery(document).on('click', '.btn-fix', (e) => {
            e.preventDefault();
            const placeId = jQuery(e.target).data('place-id');
            this.fixIndividualPlace(placeId);
        });

        jQuery(document).on('click', '.btn-regen', (e) => {
            e.preventDefault();
            const placeId = jQuery(e.target).data('place-id');
            this.regenerateMap(placeId);
        });

        jQuery(document).on('click', '.btn-view', (e) => {
            e.preventDefault();
            const placeId = jQuery(e.target).data('place-id');
            this.viewPlace(placeId);
        });
    }

    async loadInitialData() {
        try {
            this.showLoading('초기 데이터 로딩 중...');
            this.addLog('🔄 초기 데이터 로딩 시작');
            
            const response = await this.ajaxRequest('get_initial_data', {});
            
            if (response.success) {
                this.updateStatusSummary(response.data.summary);
                this.updatePlacesTable(response.data.places);
                this.addLog(`✅ 초기 데이터 로딩 완료: ${response.data.places.length}개 Places 발견`);
                this.hideLoading();
            } else {
                throw new Error(response.data || '데이터 로드 실패');
            }
        } catch (error) {
            console.error('초기 데이터 로드 오류:', error);
            this.addLog(`❌ 초기 데이터 로드 실패: ${error.message}`);
            this.showError('초기 데이터 로드 실패: ' + error.message);
            this.hideLoading();
        }
    }

    async startBatchProcessing() {
        if (this.isProcessing) {
            this.showWarning('이미 처리가 진행 중입니다.');
            return;
        }

        if (!confirm('좌표 미확정 Places에 대해 일괄 지오코딩을 시작하시겠습니까?')) {
            return;
        }

        try {
            this.isProcessing = true;
            this.progressCheckCount = 0;
            this.showProgressSection();
            this.disableButtons();
            this.clearLogs();
            
            this.addLog('🚀 일괄 지오코딩 시작');
            this.addLog('🔍 API 설정 확인 중...');
            
            const response = await this.ajaxRequest('start_batch_processing', {});
            
            if (response.success) {
                this.addLog(`✅ 배치 처리 시작: 총 ${response.data.total_count}개 Places 처리 예정`);
                
                if (response.data.total_count > 0) {
                    this.addLog(`⏱️ 예상 소요 시간: ${Math.ceil(response.data.total_count * 0.5)}초`);
                    this.addLog('📊 실시간 진행상황 모니터링 시작...');
                    
                    // 진행상황 모니터링 시작
                    this.startProgressMonitoring();
                } else {
                    this.addLog('ℹ️ 처리할 Places가 없습니다.');
                    this.stopProcessing();
                }
            } else {
                throw new Error(response.data || '배치 처리 시작 실패');
            }
        } catch (error) {
            console.error('배치 처리 시작 오류:', error);
            this.addLog(`❌ 배치 처리 시작 실패: ${error.message}`);
            this.showError('배치 처리 시작 실패: ' + error.message);
            this.stopProcessing();
        }
    }

    startProgressMonitoring() {
        this.addLog('👀 진행상황 모니터링 시작 (최대 60초)');
        
        this.progressInterval = setInterval(async () => {
            this.progressCheckCount++;
            
            // 최대 체크 횟수 초과 시 중단
            if (this.progressCheckCount > this.maxProgressChecks) {
                this.addLog('⏰ 최대 대기 시간 초과 - 진행상황 모니터링 중단');
                this.stopProgressMonitoring();
                this.handleProcessingTimeout();
                return;
            }
            
            try {
                const response = await this.ajaxRequest('check_progress', {});
                
                if (response.success) {
                    this.updateProgress(response.data);
                    
                    // 완료된 경우 모니터링 중단
                    if (response.data.completed) {
                        this.handleProcessingComplete(response.data);
                    }
                } else {
                    this.addLog(`⚠️ 진행상황 확인 실패: ${response.data || '알 수 없는 오류'}`);
                    
                    // 연속 실패 시 중단
                    if (this.progressCheckCount % 5 === 0) {
                        this.addLog('⚠️ 연속 실패 감지 - 진행상황 모니터링 곧 중단예정');
                    }
                }
            } catch (error) {
                console.error('진행상황 모니터링 오류:', error);
                this.addLog(`❌ 진행상황 모니터링 오류: ${error.message}`);
            }
        }, 1000);
    }

    stopProgressMonitoring() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
            this.progressInterval = null;
        }
        this.progressCheckCount = 0;
        this.addLog('⏹️ 진행상황 모니터링 중단');
    }

    updateProgress(progress) {
        // 프로그레스바 업데이트
        const percentage = progress.total > 0 ? Math.round((progress.processed / progress.total) * 100) : 0;
        jQuery('#progress-bar').css('width', percentage + '%');
        jQuery('.progress-percentage').text(percentage + '%');

        // 새로운 로그만 추가 (중복 방지)
        if (progress.logs && progress.logs.length > 0) {
            progress.logs.forEach(logEntry => {
                if (!this.logContainer.textContent.includes(logEntry)) {
                    this.addLog(logEntry);
                }
            });
        }

        // 현재 처리 중인 Place 표시
        if (progress.current_place) {
            this.addLog(`🔄 처리 중: ${progress.current_place}`);
        }

        // 에러 로그 추가
        if (progress.errors && progress.errors.length > 0) {
            progress.errors.forEach(error => {
                if (!this.logContainer.textContent.includes(error)) {
                    this.addLog(`❌ 오류: ${error}`);
                }
            });
        }
    }

    handleProcessingComplete(progress) {
        this.stopProgressMonitoring();
        this.isProcessing = false;
        this.enableButtons();

        const successCount = progress.success_count || 0;
        const errorCount = progress.error_count || 0;
        const totalProcessed = successCount + errorCount;

        this.addLog('🏁 배치 처리 완료!');
        this.addLog(`📊 최종 결과:`);
        this.addLog(`   ✅ 성공: ${successCount}개`);
        this.addLog(`   ❌ 실패: ${errorCount}개`);
        
        if (totalProcessed > 0) {
            this.addLog(`   📈 성공률: ${Math.round((successCount / totalProcessed) * 100)}%`);
        }

        // 완료 메시지
        if (errorCount === 0) {
            this.addLog('🎉 모든 Places가 성공적으로 처리되었습니다!');
            this.showSuccess(`✅ 배치 처리 완료! 모든 ${successCount}개 Places가 성공적으로 처리되었습니다.`);
        } else {
            this.addLog('⚠️ 일부 Places 처리에 실패했습니다.');
            this.showWarning(`⚠️ 배치 처리 완료. 성공: ${successCount}개, 실패: ${errorCount}개`);
        }

        // 데이터 새로고침
        setTimeout(() => {
            this.addLog('🔄 데이터 새로고침 중...');
            this.refreshData();
        }, 2000);
    }

    handleProcessingTimeout() {
        this.stopProcessing();
        this.addLog('⏰ 처리 시간이 초과되었습니다.');
        this.addLog('💡 수동으로 데이터를 새로고침하여 처리 결과를 확인해주세요.');
        this.showWarning('처리 시간이 초과되었습니다. 데이터를 새로고침하여 결과를 확인해주세요.');
    }

    stopProcessing() {
        this.stopProgressMonitoring();
        this.isProcessing = false;
        this.enableButtons();
        this.addLog('⏹️ 처리가 중단되었습니다.');
    }

    async fixIndividualPlace(placeId) {
        try {
            this.addLog(`🔧 개별 수정 시작: Place ID ${placeId}`);
            this.showLoading(`Place ID ${placeId} 수정 중...`);
            
            const response = await this.ajaxRequest('fix_individual_place', {
                place_id: placeId
            });
            
            if (response.success) {
                this.addLog(`✅ 개별 수정 성공: ${response.data.place.name}`);
                this.addLog(`📍 좌표: ${response.data.place.latitude}, ${response.data.place.longitude}`);
                this.showSuccess('좌표가 성공적으로 수정되었습니다.');
                this.refreshData();
            } else {
                this.addLog(`❌ 개별 수정 실패: ${response.data}`);
                throw new Error(response.data || '좌표 수정 실패');
            }
        } catch (error) {
            console.error('개별 수정 오류:', error);
            this.addLog(`❌ 개별 수정 오류: ${error.message}`);
            this.showError('좌표 수정 실패: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }

    async regenerateMap(placeId) {
        try {
            this.addLog(`🗺️ 지도 재생성 시작: Place ID ${placeId}`);
            this.showLoading(`Place ID ${placeId} 지도 재생성 중...`);
            
            const response = await this.ajaxRequest('regenerate_map', {
                place_id: placeId
            });
            
            if (response.success) {
                this.addLog(`✅ 지도 재생성 성공: Place ID ${placeId}`);
                this.showSuccess('지도가 성공적으로 재생성되었습니다.');
            } else {
                this.addLog(`❌ 지도 재생성 실패: ${response.data}`);
                throw new Error(response.data || '지도 재생성 실패');
            }
        } catch (error) {
            console.error('지도 재생성 오류:', error);
            this.addLog(`❌ 지도 재생성 오류: ${error.message}`);
            this.showError('지도 재생성 실패: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }

    viewPlace(placeId) {
        this.addLog(`👁️ Place 상세보기: Place ID ${placeId}`);
        const url = `/wp-admin/post.php?post=${placeId}&action=edit`;
        window.open(url, '_blank');
    }

    async refreshData() {
        this.addLog('🔄 데이터 새로고침 시작');
        await this.loadInitialData();
        this.addLog('✅ 데이터 새로고침 완료');
        this.showSuccess('데이터가 새로고침되었습니다.');
    }

    // 로그 관련 메서드들
    addLog(message) {
        if (!this.logContainer) {
            this.setupLogContainer();
        }
        
        if (this.logContainer) {
            const timestamp = new Date().toLocaleTimeString();
            const logLine = `[${timestamp}] ${message}\n`;
            
            this.logContainer.textContent += logLine;
            this.logContainer.scrollTop = this.logContainer.scrollHeight;
        }
        
        // 콘솔에도 출력
        console.log(`[통합지도생성] ${message}`);
    }

    clearLogs() {
        if (this.logContainer) {
            this.logContainer.textContent = '';
        }
        this.addLog('📋 로그 초기화 완료');
    }

    updateStatusSummary(summary) {
        jQuery('.status-number[data-type="pending"]').text(summary.pending_count);
        jQuery('.status-number[data-type="completed"]').text(summary.completed_count);
        jQuery('.status-number[data-type="total"]').text(summary.total_count);
        
        this.addLog(`📊 상태 업데이트: 대기 ${summary.pending_count}개, 완료 ${summary.completed_count}개, 총 ${summary.total_count}개`);
    }

    updatePlacesTable(places) {
        const tbody = jQuery('.places-table tbody');
        let html = '';
        
        if (places.length === 0) {
            html = '<tr><td colspan="5" style="text-align: center; padding: 20px;">처리할 Places가 없습니다.</td></tr>';
            this.addLog('ℹ️ 처리할 Places가 없습니다.');
        } else {
            places.forEach(place => {
                html += this.generateTableRow(place);
            });
            this.addLog(`📋 Places 테이블 업데이트: ${places.length}개 항목`);
        }
        
        tbody.html(html);
    }

    generateTableRow(place) {
        const statusBadge = this.getStatusBadge(place.coordinate_status);
        const actions = this.generateActionButtons(place.id, place.coordinate_status);

        return `
            <tr>
                <td><div class="place-name">${this.escapeHtml(place.name)}</div></td>
                <td><div class="place-address">${this.escapeHtml(place.address)}</div></td>
                <td><span class="place-category">${this.escapeHtml(place.category)}</span></td>
                <td>${statusBadge}</td>
                <td><div class="table-actions">${actions}</div></td>
            </tr>
        `;
    }

    getStatusBadge(status) {
        const badges = {
            'pending': '<span class="status-badge pending">대기중</span>',
            'completed': '<span class="status-badge completed">완료</span>',
            'error': '<span class="status-badge error">오류</span>'
        };
        return badges[status] || badges.pending;
    }

    generateActionButtons(placeId, status) {
        let buttons = [];
        
        if (status === 'pending' || status === 'error') {
            buttons.push(`<button class="btn-small btn-fix" data-place-id="${placeId}">수정</button>`);
        }
        
        if (status === 'completed') {
            buttons.push(`<button class="btn-small btn-regen" data-place-id="${placeId}">재생성</button>`);
        }
        
        buttons.push(`<button class="btn-small btn-view" data-place-id="${placeId}">보기</button>`);
        
        return buttons.join('');
    }

    showProgressSection() {
        jQuery('#progress-section').show();
    }

    disableButtons() {
        jQuery('#start-batch-processing, #refresh-data').prop('disabled', true);
    }

    enableButtons() {
        jQuery('#start-batch-processing, #refresh-data').prop('disabled', false);
    }

    async ajaxRequest(action, data = {}) {
        const requestData = {
            action: `integrated_map_${action}`,
            nonce: integrated_map_ajax.nonce,
            ...data
        };

        return new Promise((resolve, reject) => {
            jQuery.post(integrated_map_ajax.ajax_url, requestData, function(response) {
                resolve(response);
            }).fail(function(xhr, status, error) {
                reject(new Error(`AJAX 오류: ${error} (${status})`));
            });
        });
    }

    showLoading(message) {
        this.showNotice('info', message);
    }

    hideLoading() {
        this.hideNotice();
    }

    showSuccess(message) {
        this.showNotice('success', message);
    }

    showWarning(message) {
        this.showNotice('warning', message);
    }

    showError(message) {
        this.showNotice('error', message);
    }

    showNotice(type, message) {
        this.hideNotice();
        
        const notice = jQuery(`<div class="notice notice-${type}"><p>${this.escapeHtml(message)}</p></div>`);
        jQuery('.integrated-map-system').prepend(notice);
        
        if (type !== 'info') {
            setTimeout(() => {
                this.hideNotice();
            }, 5000);
        }
    }

    hideNotice() {
        jQuery('.integrated-map-system .notice').remove();
    }

    escapeHtml(unsafe) {
        return (unsafe || '')
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}

// 초기화
jQuery(document).ready(function() {
    if (jQuery('.integrated-map-system').length > 0) {
        window.IntegratedMapGeneration = new IntegratedMapGeneration();
    }
});
