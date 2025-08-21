/**
 * 성수야! V2 - 지하철역 정보 시스템
 * 
 * 위치 기반으로 가장 가까운 지하철역과 최적 출구, 도보시간을 계산
 * 
 * @package SungsuyaV2
 * @version 1.0.0
 */

class SungsuyaSubwayFinder {
    constructor() {
        this.stations = [];
        this.isLoaded = false;
        this.walkingSpeedKmh = 4; // 평균 도보 속도 4km/h
        
        console.log('🚇 SungsuyaSubwayFinder 초기화');
        this.loadStationData();
    }
    
    /**
     * 지하철역 데이터 로드
     */
    async loadStationData() {
        try {
            const response = await fetch(sungsuyaSubway.dataUrl);
            const data = await response.json();
            
            this.stations = data.stations || [];
            this.isLoaded = true;
            
            console.log(`✅ 지하철역 데이터 로드 완료: ${this.stations.length}개 역`);
            console.log('로드된 역:', this.stations.map(s => s.name).join(', '));
            
            // 로드 완료 이벤트 발생
            document.dispatchEvent(new CustomEvent('subwayDataLoaded', {
                detail: { stationCount: this.stations.length }
            }));
            
        } catch (error) {
            console.error('❌ 지하철역 데이터 로드 실패:', error);
            this.isLoaded = false;
        }
    }
    
    /**
     * 하버사인 공식을 사용한 두 지점 간 거리 계산 (km)
     */
    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // 지구 반지름 (km)
        const dLat = this.toRad(lat2 - lat1);
        const dLon = this.toRad(lon2 - lon1);
        
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(this.toRad(lat1)) * Math.cos(this.toRad(lat2)) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
        
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        const distance = R * c;
        
        return distance;
    }
    
    /**
     * 각도를 라디안으로 변환
     */
    toRad(value) {
        return value * Math.PI / 180;
    }
    
    /**
     * 거리(km)를 도보시간(분)으로 변환
     */
    calculateWalkingTime(distanceKm, speedKmh = null) {
        const speed = speedKmh || this.walkingSpeedKmh;
        const timeHours = distanceKm / speed;
        const timeMinutes = Math.round(timeHours * 60);
        
        return Math.max(1, timeMinutes); // 최소 1분
    }
    
    /**
     * 가장 가까운 지하철역 찾기
     */
    findNearestStation(latitude, longitude, maxDistanceKm = 2) {
        if (!this.isLoaded || this.stations.length === 0) {
            console.warn('⚠️ 지하철역 데이터가 로드되지 않았습니다');
            return null;
        }
        
        let nearestStation = null;
        let shortestDistance = Infinity;
        
        this.stations.forEach(station => {
            const distance = this.calculateDistance(
                latitude, longitude, 
                station.latitude, station.longitude
            );
            
            if (distance <= maxDistanceKm && distance < shortestDistance) {
                shortestDistance = distance;
                nearestStation = {
                    ...station,
                    distance: distance,
                    walkingTime: this.calculateWalkingTime(distance, station.walkingSpeedKmh)
                };
            }
        });
        
        if (nearestStation) {
            console.log(`🎯 가장 가까운 역: ${nearestStation.name} (${nearestStation.distance.toFixed(2)}km, ${nearestStation.walkingTime}분)`);
        } else {
            console.log(`📍 반경 ${maxDistanceKm}km 내에 지하철역이 없습니다`);
        }
        
        return nearestStation;
    }
    
    /**
     * 최적 출구 찾기 (목적지와 가장 가까운 방향의 출구)
     */
    findBestExit(station, destinationLat, destinationLon) {
        if (!station || !station.exits || station.exits.length === 0) {
            return null;
        }
        
        // 역에서 목적지까지의 방향 계산
        const direction = this.calculateDirection(
            station.latitude, station.longitude,
            destinationLat, destinationLon
        );
        
        // 각 출구와 목적지 방향 간의 각도 차이 계산
        let bestExit = station.exits[0];
        let smallestAngleDiff = Infinity;
        
        station.exits.forEach(exit => {
            const exitDirection = this.getExitDirection(exit.direction);
            const angleDiff = this.calculateAngleDifference(direction, exitDirection);
            
            if (angleDiff < smallestAngleDiff) {
                smallestAngleDiff = angleDiff;
                bestExit = exit;
            }
        });
        
        console.log(`🚪 최적 출구: ${bestExit.number} (${bestExit.description})`);
        
        return {
            ...bestExit,
            angleDifference: smallestAngleDiff,
            isOptimal: smallestAngleDiff < 45 // 45도 이내면 최적으로 간주
        };
    }
    
    /**
     * 두 지점 간의 방향 계산 (도 단위)
     */
    calculateDirection(lat1, lon1, lat2, lon2) {
        const dLon = this.toRad(lon2 - lon1);
        const lat1Rad = this.toRad(lat1);
        const lat2Rad = this.toRad(lat2);
        
        const y = Math.sin(dLon) * Math.cos(lat2Rad);
        const x = Math.cos(lat1Rad) * Math.sin(lat2Rad) - 
                Math.sin(lat1Rad) * Math.cos(lat2Rad) * Math.cos(dLon);
        
        let bearing = Math.atan2(y, x);
        bearing = (bearing * 180 / Math.PI + 360) % 360;
        
        return bearing;
    }
    
    /**
     * 출구 방향을 각도로 변환
     */
    getExitDirection(directionText) {
        const directions = {
            '북쪽': 0, '북동쪽': 45, '동쪽': 90, '남동쪽': 135,
            '남쪽': 180, '남서쪽': 225, '서쪽': 270, '북서쪽': 315
        };
        
        return directions[directionText] || 0;
    }
    
    /**
     * 두 방향 간의 각도 차이 계산
     */
    calculateAngleDifference(angle1, angle2) {
        let diff = Math.abs(angle1 - angle2);
        if (diff > 180) {
            diff = 360 - diff;
        }
        return diff;
    }
    
    /**
     * 완전한 지하철역 정보 조회
     */
    getStationInfo(latitude, longitude) {
        if (!this.isLoaded) {
            return {
                status: 'loading',
                message: '지하철역 데이터를 로드하고 있습니다...'
            };
        }
        
        const nearestStation = this.findNearestStation(latitude, longitude);
        
        if (!nearestStation) {
            return {
                status: 'not_found',
                message: '주변에 지하철역이 없습니다 (반경 2km)',
                hasStation: false
            };
        }
        
        const bestExit = this.findBestExit(nearestStation, latitude, longitude);
        
        return {
            status: 'found',
            hasStation: true,
            station: {
                name: nearestStation.name,
                line: nearestStation.line,
                lineColor: nearestStation.lineColor,
                distance: nearestStation.distance,
                walkingTime: nearestStation.walkingTime,
                exit: bestExit,
                allExits: nearestStation.exits,
                facilities: nearestStation.facilities,
                transferLines: nearestStation.transferLines
            },
            formattedInfo: this.formatStationInfo(nearestStation, bestExit)
        };
    }
    
    /**
     * 지하철역 정보를 표시용으로 포맷팅
     */
    formatStationInfo(station, bestExit) {
        const lineDisplay = station.transferLines.length > 0 
            ? `${station.line}, ${station.transferLines.join(', ')}`
            : station.line;
            
        const facilityIcons = {
            '엘리베이터': '🚊',
            '화장실': '🚻', 
            '무장애시설': '♿',
            'WiFi': '📶',
            '환전': '💱',
            '편의점': '🏪'
        };
        
        const facilityText = station.facilities
            .map(f => facilityIcons[f] || f)
            .join(' ');
        
        return {
            primary: `🚇 ${station.name}역 ${bestExit.number}출구`,
            secondary: `${lineDisplay} | 도보 ${station.walkingTime}분`,
            details: `${bestExit.description} | ${facilityText}`,
            walkingTime: `${station.walkingTime}분`,
            distance: `${(station.distance * 1000).toFixed(0)}m`
        };
    }
    
    /**
     * 여러 지하철역 정보 조회 (거리순 정렬)
     */
    getNearbyStations(latitude, longitude, maxDistanceKm = 2, limit = 3) {
        if (!this.isLoaded) {
            return [];
        }
        
        const nearbyStations = [];
        
        this.stations.forEach(station => {
            const distance = this.calculateDistance(
                latitude, longitude,
                station.latitude, station.longitude
            );
            
            if (distance <= maxDistanceKm) {
                const bestExit = this.findBestExit(station, latitude, longitude);
                nearbyStations.push({
                    ...station,
                    distance: distance,
                    walkingTime: this.calculateWalkingTime(distance, station.walkingSpeedKmh),
                    bestExit: bestExit,
                    formattedInfo: this.formatStationInfo({
                        ...station,
                        distance: distance,
                        walkingTime: this.calculateWalkingTime(distance, station.walkingSpeedKmh)
                    }, bestExit)
                });
            }
        });
        
        // 거리순 정렬
        nearbyStations.sort((a, b) => a.distance - b.distance);
        
        return nearbyStations.slice(0, limit);
    }
}

// 전역 변수로 설정
window.SungsuyaSubwayFinder = SungsuyaSubwayFinder;

// DOM 로드 완료 후 자동 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 관리자 페이지에서만 실행
    if (document.getElementById('store-address-search') && typeof sungsuyaSubway !== 'undefined') {
        console.log('🚇 지하철역 정보 시스템 시작');
        window.sungsuyaSubwayFinder = new SungsuyaSubwayFinder();
    }
});

console.log('🚇 SungsuyaSubwayFinder 스크립트 로드 완료');
