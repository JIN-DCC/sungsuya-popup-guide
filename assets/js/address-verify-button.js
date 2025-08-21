/**
 * 주소 확인 버튼 기능 추가
 * 
 * 주소 입력 후 수동으로 좌표를 변환하는 버튼 추가
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // 주소 입력 필드 찾기
        const $addressField = $('#store_address');
        const $latField = $('#store_latitude');
        const $lngField = $('#store_longitude');
        
        if ($addressField.length === 0) {
            console.log('주소 필드를 찾을 수 없습니다.');
            return;
        }
        
        // 주소 확인 버튼 추가
        const $verifyButton = $('<button type="button" class="button button-secondary address-verify-btn">주소 확인</button>');
        const $statusMessage = $('<span class="address-verify-status" style="margin-left: 10px;"></span>');
        
        // 스타일 추가
        const style = `
            <style>
                .address-verify-btn {
                    margin-left: 10px;
                    position: relative;
                }
                .address-verify-btn.loading {
                    opacity: 0.7;
                    pointer-events: none;
                }
                .address-verify-btn.loading::after {
                    content: '';
                    position: absolute;
                    width: 16px;
                    height: 16px;
                    top: 50%;
                    left: 50%;
                    margin: -8px 0 0 -8px;
                    border: 2px solid #f3f3f3;
                    border-radius: 50%;
                    border-top: 2px solid #333;
                    animation: spin 1s linear infinite;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                .address-verify-status {
                    font-weight: 600;
                }
                .address-verify-status.success {
                    color: #46b450;
                }
                .address-verify-status.error {
                    color: #dc3232;
                }
            </style>
        `;
        
        $('head').append(style);
        
        // 버튼과 상태 메시지를 주소 필드 뒤에 추가
        $addressField.after($statusMessage).after($verifyButton);
        
        // 버튼 클릭 이벤트
        $verifyButton.on('click', function() {
            const address = $addressField.val().trim();
            
            if (!address) {
                showStatus('주소를 입력해주세요.', 'error');
                return;
            }
            
            // 로딩 상태
            $verifyButton.addClass('loading').text('확인 중...');
            showStatus('주소를 확인하고 있습니다...', '');
            
            // 네이버 지도 API로 주소 검색
            if (typeof naver !== 'undefined' && naver.maps && naver.maps.Service) {
                naver.maps.Service.geocode({
                    query: address
                }, function(status, response) {
                    $verifyButton.removeClass('loading').text('주소 확인');
                    
                    if (status === naver.maps.Service.Status.OK) {
                        if (response.v2.addresses && response.v2.addresses.length > 0) {
                            const result = response.v2.addresses[0];
                            const lat = parseFloat(result.y);
                            const lng = parseFloat(result.x);
                            
                            // 좌표 입력
                            $latField.val(lat.toFixed(6));
                            $lngField.val(lng.toFixed(6));
                            
                            // 상태 메시지
                            showStatus('✅ 주소가 확인되었습니다.', 'success');
                            
                            // 지도 업데이트 (관리자 페이지에 지도가 있는 경우)
                            if (typeof updateMapLocation === 'function') {
                                updateMapLocation(lat, lng, result.roadAddress || result.jibunAddress);
                            }
                            
                            // 변경 이벤트 트리거
                            $latField.trigger('change');
                            $lngField.trigger('change');
                            
                            console.log('주소 확인 성공:', {
                                address: result.roadAddress || result.jibunAddress,
                                lat: lat,
                                lng: lng
                            });
                        } else {
                            showStatus('❌ 주소를 찾을 수 없습니다. 다시 확인해주세요.', 'error');
                        }
                    } else {
                        showStatus('❌ 주소 검색에 실패했습니다.', 'error');
                        console.error('Geocoding 실패:', status);
                    }
                });
            } else {
                // 폴백: AJAX 요청
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'verify_address',
                        address: address,
                        nonce: $('#places_location_nonce').val() || ''
                    },
                    success: function(response) {
                        $verifyButton.removeClass('loading').text('주소 확인');
                        
                        if (response.success && response.data) {
                            $latField.val(response.data.latitude);
                            $lngField.val(response.data.longitude);
                            showStatus('✅ 주소가 확인되었습니다.', 'success');
                            
                            // 변경 이벤트 트리거
                            $latField.trigger('change');
                            $lngField.trigger('change');
                        } else {
                            showStatus('❌ ' + (response.data || '주소를 찾을 수 없습니다.'), 'error');
                        }
                    },
                    error: function() {
                        $verifyButton.removeClass('loading').text('주소 확인');
                        showStatus('❌ 서버 오류가 발생했습니다.', 'error');
                    }
                });
            }
        });
        
        // 상태 메시지 표시 함수
        function showStatus(message, type) {
            $statusMessage
                .removeClass('success error')
                .addClass(type)
                .text(message);
            
            if (message) {
                setTimeout(function() {
                    $statusMessage.fadeOut(function() {
                        $(this).text('').show();
                    });
                }, 5000);
            }
        }
        
        // 엔터키로도 주소 확인 가능
        $addressField.on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $verifyButton.click();
            }
        });
    });
    
})(jQuery);
