/**
 * 디버그 로그 유틸리티
 * 프로덕션 환경에서는 콘솔 로그를 출력하지 않음
 */
(function(window) {
    'use strict';
    
    // 개발 환경 감지
    const isDevelopment = () => {
        return (
            window.location.hostname === 'localhost' || 
            window.location.hostname === 'sungsuya-v2.local' ||
            window.location.hostname.includes('.local') ||
            window.location.hostname === '127.0.0.1' ||
            (typeof WP_DEBUG !== 'undefined' && WP_DEBUG === true)
        );
    };
    
    // 디버그 로그 래퍼
    const createDebugLogger = () => {
        const isDebug = isDevelopment();
        
        return {
            log: (...args) => {
                if (isDebug) {
                    console.log(...args);
                }
            },
            error: (...args) => {
                // 에러는 항상 출력
                console.error(...args);
            },
            warn: (...args) => {
                if (isDebug) {
                    console.warn(...args);
                }
            },
            info: (...args) => {
                if (isDebug) {
                    console.info(...args);
                }
            },
            debug: (...args) => {
                if (isDebug) {
                    console.debug(...args);
                }
            },
            table: (...args) => {
                if (isDebug) {
                    console.table(...args);
                }
            },
            group: (...args) => {
                if (isDebug) {
                    console.group(...args);
                }
            },
            groupEnd: () => {
                if (isDebug) {
                    console.groupEnd();
                }
            }
        };
    };
    
    // 전역 객체에 추가
    window.SungsuyaDebug = createDebugLogger();
    window.isDevelopment = isDevelopment();
    
    // 간단한 사용을 위한 전역 함수
    window.debugLog = window.SungsuyaDebug.log;
    
})(window);
