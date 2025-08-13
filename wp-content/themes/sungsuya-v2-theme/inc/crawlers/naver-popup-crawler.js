const puppeteer = require('puppeteer');

/**
 * 네이버 검색 팝업스토어 크롤러
 * 심플하고 실용적인 구현
 */
class NaverPopupCrawler {
    constructor() {
        this.results = [];
    }

    async crawl(keyword = '성수동 팝업스토어') {
        let browser;
        try {
            browser = await puppeteer.launch({
                headless: true,
                args: ['--no-sandbox', '--disable-setuid-sandbox']
            });

            const page = await browser.newPage();
            await page.setViewport({ width: 1280, height: 800 });

            // 네이버 검색
            const searchUrl = `https://search.naver.com/search.naver?query=${encodeURIComponent(keyword)}&where=view&sm=tab_jum&nso=`;
            await page.goto(searchUrl, { waitUntil: 'networkidle2' });

            // 검색 결과 파싱
            const blogResults = await page.evaluate(() => {
                const results = [];
                const items = document.querySelectorAll('.view_wrap');

                items.forEach((item, index) => {
                    if (index >= 20) return; // 최대 20개

                    const titleEl = item.querySelector('.title_link');
                    const descEl = item.querySelector('.dsc_area');
                    const linkEl = item.querySelector('.title_link');
                    
                    if (titleEl && descEl) {
                        const text = titleEl.textContent + ' ' + descEl.textContent;
                        
                        // 팝업스토어 관련 키워드 체크
                        if (text.match(/팝업|pop.?up|플래그십|체험/i)) {
                            results.push({
                                title: titleEl.textContent.trim(),
                                description: descEl.textContent.trim(),
                                link: linkEl ? linkEl.href : '',
                                text: text
                            });
                        }
                    }
                });

                return results;
            });

            // 결과 처리
            for (const result of blogResults) {
                const info = this.extractInfo(result.text);
                if (info) {
                    info.source_url = result.link;
                    info.source = 'naver';
                    this.results.push(info);
                }
            }

        } catch (error) {
            console.error('크롤링 오류:', error);
        } finally {
            if (browser) await browser.close();
        }

        return this.results;
    }

    extractInfo(text) {
        const info = {};

        // 브랜드/팝업 이름 추출
        const namePatterns = [
            /([가-힣A-Za-z0-9\s]+)\s*팝업스토어?/,
            /([가-힣A-Za-z0-9\s]+)\s*pop.?up/i,
            /([가-힣A-Za-z0-9\s]+)\s*플래그십/,
        ];

        for (const pattern of namePatterns) {
            const match = text.match(pattern);
            if (match) {
                info.name = match[1].trim() + ' 팝업스토어';
                break;
            }
        }

        // 주소 추출
        const addrPattern = /성수동?\s*([가-힣0-9\-\s]+길\s*[0-9\-]+)/;
        const addrMatch = text.match(addrPattern);
        if (addrMatch) {
            info.address = '서울 성동구 ' + addrMatch[0];
        }

        // 기간 추출
        const periodPattern = /(\d{1,2}[\.\/]\d{1,2})\s*[-~]\s*(\d{1,2}[\.\/]\d{1,2})/;
        const periodMatch = text.match(periodPattern);
        if (periodMatch) {
            const year = new Date().getFullYear();
            info.period = `${year}.${periodMatch[1]} - ${year}.${periodMatch[2]}`;
        }

        // 최소 정보가 있는 경우만 반환
        if (info.name && (info.address || info.period)) {
            info.confidence = 3; // 기본 신뢰도
            return info;
        }

        return null;
    }
}

// CLI 실행
if (require.main === module) {
    const crawler = new NaverPopupCrawler();
    crawler.crawl().then(results => {
        console.log(JSON.stringify(results, null, 2));
    });
}

module.exports = NaverPopupCrawler;
