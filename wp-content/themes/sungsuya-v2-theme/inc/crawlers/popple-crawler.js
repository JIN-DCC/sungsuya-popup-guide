const puppeteer = require('puppeteer');

/**
 * 팝플(Popple) 사이트 크롤러
 * 팝업스토어 전문 사이트라 신뢰도 높음
 */
class PoppleCrawler {
    constructor() {
        this.results = [];
    }

    async crawl(keyword = '성수') {
        let browser;
        try {
            browser = await puppeteer.launch({
                headless: true,
                args: ['--no-sandbox', '--disable-setuid-sandbox']
            });

            const page = await browser.newPage();
            await page.setViewport({ width: 1280, height: 800 });

            // 팝플 검색 페이지
            const searchUrl = `https://www.popple.co.kr/popup/list?keyword=${encodeURIComponent(keyword)}`;
            await page.goto(searchUrl, { waitUntil: 'networkidle2' });

            // 팝업스토어 목록 파싱
            const popupStores = await page.evaluate(() => {
                const results = [];
                const items = document.querySelectorAll('.popup-item, .popup-card, [class*="popup"]');

                items.forEach(item => {
                    const nameEl = item.querySelector('[class*="name"], [class*="title"], h3, h4');
                    const addressEl = item.querySelector('[class*="address"], [class*="location"]');
                    const periodEl = item.querySelector('[class*="period"], [class*="date"]');
                    const brandEl = item.querySelector('[class*="brand"]');

                    if (nameEl) {
                        const result = {
                            name: nameEl.textContent.trim(),
                            address: addressEl ? addressEl.textContent.trim() : '',
                            period: periodEl ? periodEl.textContent.trim() : '',
                            brand: brandEl ? brandEl.textContent.trim() : ''
                        };

                        // 성수동 관련만 필터
                        if (result.address && (result.address.includes('성수') || result.address.includes('성동구'))) {
                            results.push(result);
                        }
                    }
                });

                return results;
            });

            // 결과 처리
            for (const store of popupStores) {
                const info = {
                    name: store.name,
                    address: this.normalizeAddress(store.address),
                    period: store.period,
                    brand: store.brand || this.extractBrandFromName(store.name),
                    source: 'popple',
                    confidence: 5, // 전문 사이트라 높은 신뢰도
                    raw_data: store
                };

                // 기간 파싱
                if (info.period) {
                    const dates = this.parsePeriod(info.period);
                    if (dates) {
                        info.start_date = dates.start;
                        info.end_date = dates.end;
                    }
                }

                this.results.push(info);
            }

        } catch (error) {
            console.error('팝플 크롤링 오류:', error);
        } finally {
            if (browser) await browser.close();
        }

        return this.results;
    }

    normalizeAddress(address) {
        if (!address) return '서울 성동구';
        
        // 주소가 완전하지 않으면 보완
        if (!address.includes('서울')) {
            return '서울 성동구 ' + address;
        }
        return address;
    }

    extractBrandFromName(name) {
        // 이름에서 브랜드 추출
        const match = name.match(/^([가-힣A-Za-z0-9\s]+?)(?:\s*[xX]\s*|\s*팝업|\s*pop)/i);
        return match ? match[1].trim() : name;
    }

    parsePeriod(period) {
        // "2025.1.15 ~ 2025.2.15" 형식 파싱
        const match = period.match(/(\d{4}[.\-/]?\d{1,2}[.\-/]?\d{1,2})\s*[~\-]\s*(\d{4}[.\-/]?\d{1,2}[.\-/]?\d{1,2})/);
        if (match) {
            return {
                start: match[1].replace(/[.\-/]/g, '-'),
                end: match[2].replace(/[.\-/]/g, '-')
            };
        }
        return null;
    }
}

// CLI 실행
if (require.main === module) {
    const crawler = new PoppleCrawler();
    crawler.crawl().then(results => {
        console.log(JSON.stringify(results, null, 2));
    });
}

module.exports = PoppleCrawler;
