// 투어플래너 핵심 JavaScript 파일
// IntegratedTourPlanner 클래스가 정의되지 않았을 때를 위한 백업 코드

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 투어플래너 초기화 시작');
    
    // IntegratedTourPlanner 클래스가 있는지 확인
    if (typeof IntegratedTourPlanner === 'undefined') {
        console.log('⚠️ IntegratedTourPlanner 클래스가 정의되지 않음 - 대체 구현 사용');
        
        // 간단한 대체 구현
        window.tourPlanner = {
            selectedPlaces: [],
            
            quickAddPlaces: function(type) {
                console.log('quickAddPlaces 호출:', type);
                // 팝업 대신 페이지로 이동
                window.location.href = `/places?place_type=${type}`;
            },
            
            selectTourPurpose: function(purpose) {
                console.log('selectTourPurpose 호출:', purpose);
                // 버튼 활성화 처리
                document.querySelectorAll('.purpose-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                const selectedBtn = document.querySelector(`[data-purpose="${purpose}"]`);
                if (selectedBtn) {
                    selectedBtn.classList.add('active');
                }
            },
            
            optimizeRoute: function() {
                console.log('optimizeRoute 호출');
                if (this.selectedPlaces.length < 2) {
                    alert('최소 2개 이상의 장소를 선택해주세요');
                } else {
                    alert('경로 최적화 기능은 준비 중입니다.');
                }
            },
            
            showTourGuide: function() {
                console.log('showTourGuide 호출');
                if (this.selectedPlaces.length === 0) {
                    alert('먼저 장소를 선택해주세요');
                } else {
                    alert('투어 가이드 기능은 준비 중입니다.');
                }
            },
            
            saveTour: function() {
                console.log('saveTour 호출');
                if (this.selectedPlaces.length === 0) {
                    alert('먼저 장소를 선택해주세요');
                } else {
                    alert('투어 저장 기능은 준비 중입니다.');
                }
            },
            
            shareTour: function() {
                console.log('shareTour 호출');
                if (this.selectedPlaces.length === 0) {
                    alert('먼저 장소를 선택해주세요');
                } else {
                    alert('투어 공유 기능은 준비 중입니다.');
                }
            },
            
            closeQuickAddModal: function() {
                const modal = document.querySelector('.place-modal-overlay');
                if (modal) modal.remove();
            },
            
            addPlaceFromModal: function(placeId, placeTitle, placeType) {
                console.log('장소 추가:', placeId, placeTitle, placeType);
                this.selectedPlaces.push({
                    id: placeId,
                    title: placeTitle,
                    place_type: placeType
                });
                this.closeQuickAddModal();
                alert(`${placeTitle}이(가) 투어에 추가되었습니다!`);
                this.updateUI();
            },
            
            removePlace: function(placeId) {
                const index = this.selectedPlaces.findIndex(p => p.id == placeId);
                if (index !== -1) {
                    const place = this.selectedPlaces[index];
                    this.selectedPlaces.splice(index, 1);
                    alert(`${place.title}이(가) 투어에서 제거되었습니다`);
                    this.updateUI();
                }
            },
            
            updateUI: function() {
                // 장소 개수 업데이트
                const countElement = document.getElementById('places-count');
                if (countElement) {
                    countElement.textContent = `${this.selectedPlaces.length}곳`;
                }
                
                // 버튼 활성화 상태 업데이트
                const hasPlaces = this.selectedPlaces.length > 0;
                document.getElementById('optimize-btn').disabled = !hasPlaces;
                document.getElementById('guide-btn').disabled = !hasPlaces;
                document.getElementById('save-tour-btn').disabled = !hasPlaces;
                document.getElementById('share-tour-btn').disabled = !hasPlaces;
                
                // 선택된 장소 목록 업데이트
                const listContainer = document.getElementById('selected-places-list');
                if (listContainer) {
                    if (this.selectedPlaces.length === 0) {
                        listContainer.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-icon">🗺️</div>
                                <p>장소를 선택하면<br>여기에 표시됩니다</p>
                                <button class="btn-empty" onclick="showPlaceSelector()">
                                    장소 선택하기
                                </button>
                            </div>
                        `;
                    } else {
                        listContainer.innerHTML = this.selectedPlaces.map((place, index) => `
                            <div class="selected-place">
                                <div class="place-number">${index + 1}</div>
                                <div class="place-info">
                                    <div class="place-name">${place.title}</div>
                                    <div class="place-type">${place.place_type}</div>
                                </div>
                                <button class="remove-place" data-place-id="${place.id}" 
                                        onclick="tourPlanner.removePlace('${place.id}')">✕</button>
                            </div>
                        `).join('');
                    }
                }
            }
        };
        
        console.log('✅ tourPlanner 대체 구현 완료');
    } else {
        // IntegratedTourPlanner 클래스가 있으면 인스턴스 생성
        window.tourPlanner = new IntegratedTourPlanner();
        console.log('✅ IntegratedTourPlanner 인스턴스 생성 완료');
    }
    
    // 전역 함수 정의
    window.showPlaceSelector = () => {
        console.log('showPlaceSelector 호출됨');
        window.location.href = '/places';
    };
    
    // 이벤트 바인딩
    // 투어 목적 선택
    document.querySelectorAll('.purpose-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const purposeBtn = e.target.closest('.purpose-btn');
            if (purposeBtn && window.tourPlanner) {
                window.tourPlanner.selectTourPurpose(purposeBtn.dataset.purpose);
            }
        });
    });

    // 빠른 장소 추가
    document.querySelectorAll('.quick-add-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const addBtn = e.target.closest('.quick-add-btn');
            if (addBtn && window.tourPlanner) {
                window.tourPlanner.quickAddPlaces(addBtn.dataset.type);
            }
        });
    });

    // 경로 최적화
    const optimizeBtn = document.getElementById('optimize-btn');
    if (optimizeBtn) {
        optimizeBtn.addEventListener('click', () => {
            if (window.tourPlanner) {
                window.tourPlanner.optimizeRoute();
            }
        });
    }

    // 투어 가이드
    const guideBtn = document.getElementById('guide-btn');
    if (guideBtn) {
        guideBtn.addEventListener('click', () => {
            if (window.tourPlanner) {
                window.tourPlanner.showTourGuide();
            }
        });
    }
    
    // 투어 저장
    const saveTourBtn = document.getElementById('save-tour-btn');
    if (saveTourBtn) {
        saveTourBtn.addEventListener('click', () => {
            if (window.tourPlanner) {
                window.tourPlanner.saveTour();
            }
        });
    }
    
    // 투어 공유
    const shareTourBtn = document.getElementById('share-tour-btn');
    if (shareTourBtn) {
        shareTourBtn.addEventListener('click', () => {
            if (window.tourPlanner) {
                window.tourPlanner.shareTour();
            }
        });
    }
    
    console.log('🎯 투어플래너 초기화 완료');
});
