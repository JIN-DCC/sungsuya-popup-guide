// 투어플래너 초기화 및 핵심 기능
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 투어플래너 초기화 시작 (tour-planner-init.js)');
    
    // 전역 함수 정의
    window.showPlaceSelector = function() {
        console.log('showPlaceSelector 호출됨');
        window.location.href = '/places';
    };
    
    // 전역 tourPlanner 객체 생성
    window.tourPlanner = {
        selectedPlaces: [],
        
        // 빠른 장소 추가 모달 표시
        async quickAddPlaces(type) {
            console.log('quickAddPlaces 호출:', type);
            
            try {
                // API에서 장소 데이터 가져오기 - 실제 API 엔드포인트 사용
                const response = await fetch(`/wp-json/wp/v2/places?place_type=${type}&per_page=20&_fields=id,title,place_type,meta`, {
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error('API 호출 실패');
                }
                
                const places = await response.json();
                console.log('로드된 장소:', places);
                
                if (places.length === 0) {
                    alert('해당 유형의 장소가 없습니다.');
                    return;
                }
                
                // 메타데이터 포함하여 모달 표시
                const placesWithMeta = await this.loadPlacesWithMeta(places);
                this.showQuickAddModal(type, placesWithMeta);
                
            } catch (error) {
                console.error('장소 로드 실패:', error);
                // 실패시 기존 방식으로 페이지 이동
                window.location.href = `/places?place_type=${type}`;
            }
        },
        
        // 장소별 메타데이터 로드
        async loadPlacesWithMeta(places) {
            const placesWithMeta = [];
            
            for (const place of places) {
                try {
                    // 각 장소의 메타데이터 가져오기
                    const metaResponse = await fetch(`/wp-json/sungsuya/v1/places/${place.id}/meta`);
                    if (metaResponse.ok) {
                        const meta = await metaResponse.json();
                        placesWithMeta.push({
                            ...place,
                            location: {
                                lat: parseFloat(meta.latitude) || 37.5444,
                                lng: parseFloat(meta.longitude) || 127.0557,
                                address: meta.address || '성수동'
                            }
                        });
                    } else {
                        // 메타데이터 실패 시 기본값 사용
                        placesWithMeta.push({
                            ...place,
                            location: {
                                lat: 37.5444,
                                lng: 127.0557,
                                address: '성수동'
                            }
                        });
                    }
                } catch (error) {
                    console.error(`메타데이터 로드 실패 (ID: ${place.id}):`, error);
                    placesWithMeta.push({
                        ...place,
                        location: {
                            lat: 37.5444,
                            lng: 127.0557,
                            address: '성수동'
                        }
                    });
                }
            }
            
            return placesWithMeta;
        },
        
        // 모달 표시
        showQuickAddModal(type, places) {
            const typeLabels = {
                'popup_store': '팝업스토어',
                'restaurant': '맛집',
                'retail_store': '상설매장',
                'facility': '편의시설'
            };
            
            const typeIcons = {
                'popup_store': '🏪',
                'restaurant': '🍽️',
                'retail_store': '🏬',
                'facility': '🚻'
            };
            
            const typeLabel = typeLabels[type] || '장소';
            const typeIcon = typeIcons[type] || '📍';
            
            // 모달 HTML 생성
            const modalHTML = `
                <div class="place-modal-overlay" style="
                    position: fixed; 
                    top: 0; left: 0; right: 0; bottom: 0; 
                    background: rgba(0,0,0,0.6); 
                    z-index: 1000; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center;
                    backdrop-filter: blur(4px);
                ">
                    <div class="place-modal-content" style="
                        background: white; 
                        border-radius: 1rem; 
                        padding: 0;
                        max-width: 500px; 
                        max-height: 80vh; 
                        overflow: hidden;
                        margin: 1rem;
                        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                    ">
                        <div class="modal-header" style="
                            padding: 1.5rem;
                            border-bottom: 1px solid #e5e7eb;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            color: white;
                        ">
                            <h3 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                                <span style="font-size: 1.5rem;">${typeIcon}</span>
                                ${typeLabel} 선택
                            </h3>
                            <button onclick="tourPlanner.closeQuickAddModal()" style="
                                background: none; 
                                border: none; 
                                font-size: 1.5rem; 
                                cursor: pointer;
                                color: white;
                                padding: 0.25rem;
                                border-radius: 0.25rem;
                            ">✕</button>
                        </div>
                        <div class="modal-body" style="
                            padding: 1rem;
                            max-height: 400px;
                            overflow-y: auto;
                        ">
                            <div class="places-grid">
                                ${places.map(place => `
                                    <div class="place-option" style="
                                        display: flex;
                                        align-items: center;
                                        gap: 1rem;
                                        padding: 1rem;
                                        border: 2px solid #e5e7eb;
                                        border-radius: 0.5rem;
                                        margin-bottom: 0.75rem;
                                        cursor: pointer;
                                        transition: all 0.2s;
                                        background: #f8fafc;
                                    " 
                                    onmouseover="this.style.borderColor='#3b82f6'; this.style.background='#f0f9ff';" 
                                    onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='#f8fafc';"
                                    onclick="tourPlanner.addPlaceFromModal('${place.id}', '${(place.title.rendered || place.title || '').replace(/'/g, "\\'")}', '${type}')">
                                        <div style="
                                            width: 40px;
                                            height: 40px;
                                            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
                                            border-radius: 50%;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            font-size: 1.2rem;
                                            flex-shrink: 0;
                                        ">${typeIcon}</div>
                                        <div style="flex: 1;">
                                            <div style="
                                                font-weight: 600;
                                                color: #1f2937;
                                                margin-bottom: 0.25rem;
                                            ">${place.title.rendered || place.title || '장소명 없음'}</div>
                                            <div style="
                                                font-size: 0.8rem;
                                                color: #6b7280;
                                            ">${place.location?.address || '성수동'}</div>
                                        </div>
                                        <div style="
                                            padding: 0.25rem 0.75rem;
                                            background: #3b82f6;
                                            color: white;
                                            border-radius: 1rem;
                                            font-size: 0.8rem;
                                            font-weight: 600;
                                        ">추가</div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        <div class="modal-footer" style="
                            padding: 1rem 1.5rem;
                            border-top: 1px solid #e5e7eb;
                            background: #f8fafc;
                            display: flex;
                            gap: 0.5rem;
                        ">
                            <button onclick="tourPlanner.closeQuickAddModal()" style="
                                flex: 1;
                                padding: 0.75rem;
                                background: #e5e7eb;
                                color: #374151;
                                border: none;
                                border-radius: 0.5rem;
                                cursor: pointer;
                                font-weight: 600;
                            ">취소</button>
                            <button onclick="window.location.href='/places?place_type=${type}'" style="
                                flex: 1;
                                padding: 0.75rem;
                                background: #3b82f6;
                                color: white;
                                border: none;
                                border-radius: 0.5rem;
                                cursor: pointer;
                                font-weight: 600;
                            ">더 많은 장소 보기</button>
                        </div>
                    </div>
                </div>
            `;
            
            // 모달을 DOM에 추가
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        },
        
        // 모달 닫기
        closeQuickAddModal() {
            const modal = document.querySelector('.place-modal-overlay');
            if (modal) {
                modal.remove();
            }
        },
        
        // 모달에서 장소 추가
        addPlaceFromModal(placeId, placeTitle, placeType) {
            console.log('장소 추가:', placeId, placeTitle, placeType);
            
            const selectedStores = JSON.parse(localStorage.getItem('selectedStores') || '[]');
            
            // 중복 체크
            if (selectedStores.find(p => p.id == placeId)) {
                alert('이미 선택된 장소입니다');
                return;
            }
            
            // 최대 8개 제한
            if (selectedStores.length >= 8) {
                alert('최대 8개 장소까지 선택 가능합니다');
                return;
            }
            
            // API에서 장소 상세 정보 가져오기
            fetch(`/wp-json/sungsuya/v1/places/${placeId}/meta`)
                .then(response => response.json())
                .then(meta => {
                    // 장소 추가 (location 정보 포함)
                    selectedStores.push({
                        id: placeId,
                        title: placeTitle,
                        name: placeTitle, // 호환성을 위해 name도 추가
                        place_type: placeType,
                        location: {
                            lat: parseFloat(meta.latitude) || 37.5444,
                            lng: parseFloat(meta.longitude) || 127.0557,
                            address: meta.address || '성수동'
                        }
                    });
                    
                    localStorage.setItem('selectedStores', JSON.stringify(selectedStores));
                    
                    this.showMessage(`${placeTitle}이(가) 투어에 추가되었습니다! ✨`);
                    
                    // 페이지 새로고침하여 UI 업데이트
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                })
                .catch(error => {
                    console.error('장소 정보 가져오기 실패:', error);
                    
                    // 실패해도 기본 정보로 추가
                    selectedStores.push({
                        id: placeId,
                        title: placeTitle,
                        place_type: placeType,
                        location: {
                            lat: 37.5444,
                            lng: 127.0557,
                            address: '성수동'
                        }
                    });
                    
                    localStorage.setItem('selectedStores', JSON.stringify(selectedStores));
                    
                    this.showMessage(`${placeTitle}이(가) 투어에 추가되었습니다!`);
                    
                    // 페이지 새로고침하여 UI 업데이트
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                });
        },
        
        // 투어 목적 선택
        selectTourPurpose(purpose) {
            console.log('selectTourPurpose 호출:', purpose);
            
            // 버튼 활성화 처리
            document.querySelectorAll('.purpose-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            const selectedBtn = document.querySelector(`[data-purpose="${purpose}"]`);
            if (selectedBtn) {
                selectedBtn.classList.add('active');
            }
            
            // 목적에 따른 메시지 표시
            const messages = {
                'quick': '⚡ 빠른 투어를 선택했습니다. 핵심 장소 3-4곳을 추천드립니다!',
                'detailed': '🕒 여유 투어를 선택했습니다. 다양한 장소 5-6곳을 추천드립니다!',
                'custom': '🎯 직접 선택 모드입니다. 원하는 장소를 자유롭게 선택하세요!'
            };
            
            if (messages[purpose]) {
                this.showMessage(messages[purpose]);
            }
        },
        
        // 메시지 표시
        showMessage(message) {
            // 기존 토스트 제거
            const existingToast = document.querySelector('.tour-toast');
            if (existingToast) {
                existingToast.remove();
            }

            // 새 토스트 생성
            const toast = document.createElement('div');
            toast.className = 'tour-toast';
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: rgba(0, 0, 0, 0.9);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 0.5rem;
                z-index: 10000;
                max-width: 90%;
                text-align: center;
                font-size: 0.9rem;
                font-weight: 500;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            `;
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            // 3초 후 제거
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 3000);
        },
        
        // 기타 스텁 함수들
        optimizeRoute() {
            alert('경로 최적화 기능은 준비 중입니다.');
        },
        
        showTourGuide() {
            alert('투어 가이드 기능은 준비 중입니다.');
        },
        
        saveTour() {
            alert('투어 저장 기능은 준비 중입니다.');
        },
        
        shareTour() {
            alert('투어 공유 기능은 준비 중입니다.');
        },
        
        showLoginModal() {
            window.location.href = '/wp-login.php?redirect_to=' + encodeURIComponent(window.location.pathname);
        },
        
        // 선택된 장소 초기화
        clearAllPlaces() {
            if (confirm('선택된 모든 장소를 제거하시겠습니까?')) {
                localStorage.setItem('selectedStores', '[]');
                this.showMessage('모든 장소가 제거되었습니다.');
                window.location.reload();
            }
        },
        
        // 디버그: 현재 선택된 장소 확인
        debugShowPlaces() {
            const selectedStores = JSON.parse(localStorage.getItem('selectedStores') || '[]');
            console.log('현재 선택된 장소:', selectedStores);
            console.log('선택된 장소 수:', selectedStores.length);
            return selectedStores;
        }
    };
    
    // 현재 선택된 장소 수 확인
    const currentPlaces = JSON.parse(localStorage.getItem('selectedStores') || '[]');
    console.log(`📍 현재 선택된 장소: ${currentPlaces.length}개`);
    
    // 이벤트 바인딩
    bindEvents();
    
    console.log('✅ 투어플래너 초기화 완료');
});

// 이벤트 바인딩 함수
function bindEvents() {
    // 투어 목적 선택
    document.querySelectorAll('.purpose-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const purposeBtn = e.target.closest('.purpose-btn');
            if (purposeBtn) {
                tourPlanner.selectTourPurpose(purposeBtn.dataset.purpose);
            }
        });
    });

    // 빠른 장소 추가
    document.querySelectorAll('.quick-add-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const addBtn = e.target.closest('.quick-add-btn');
            if (addBtn) {
                tourPlanner.quickAddPlaces(addBtn.dataset.type);
            }
        });
    });

    // 경로 최적화
    const optimizeBtn = document.getElementById('optimize-btn');
    if (optimizeBtn) {
        optimizeBtn.addEventListener('click', () => tourPlanner.optimizeRoute());
    }

    // 투어 가이드
    const guideBtn = document.getElementById('guide-btn');
    if (guideBtn) {
        guideBtn.addEventListener('click', () => tourPlanner.showTourGuide());
    }
    
    // 투어 저장
    const saveTourBtn = document.getElementById('save-tour-btn');
    if (saveTourBtn) {
        saveTourBtn.addEventListener('click', () => tourPlanner.saveTour());
    }
    
    // 투어 공유
    const shareTourBtn = document.getElementById('share-tour-btn');
    if (shareTourBtn) {
        shareTourBtn.addEventListener('click', () => tourPlanner.shareTour());
    }
    
    // 장소 제거
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-place')) {
            const placeId = e.target.dataset.placeId;
            removePlace(placeId);
        }
    });
}

// 장소 제거 함수
function removePlace(placeId) {
    const selectedStores = JSON.parse(localStorage.getItem('selectedStores') || '[]');
    const index = selectedStores.findIndex(p => p.id == placeId);
    
    if (index !== -1) {
        const place = selectedStores[index];
        selectedStores.splice(index, 1);
        localStorage.setItem('selectedStores', JSON.stringify(selectedStores));
        
        tourPlanner.showMessage(`${place.title}이(가) 투어에서 제거되었습니다`);
        
        // 페이지 새로고침하여 UI 업데이트
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
}
