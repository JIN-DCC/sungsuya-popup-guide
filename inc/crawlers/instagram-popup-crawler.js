const { chromium } = require('playwright');

/**
 * 인스타그램 해시태그 크롤러
 * 심플한 구현 - 공개 게시물만 수집
 */
class InstagramPopupCrawler {
    constructor() {
        this.results = [];
    }

    async crawl(hashtag = '성수동팝업') {
        let browser;
        try {
            browser = await chromium.launch({
                headless: true,
                args: ['--no-sandbox', '--disable-setuid-sandbox']
            });

            const context = await browser.newContext({
                userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            });

            const page = await context.newPage();

            // 인스타그램 해시태그 페이지
            const url = `https://www.instagram.com/explore/tags/${encodeURIComponent(hashtag)}/`;
            await page.goto(url, { waitUntil: 'networkidle' });

            // 로그인 팝업 닫기
            try {
                await page.click('button:has-text("나중에 하기")', { timeout: 3000 });
            } catch (e) {
                // 팝업이 없으면 계속 진행
            }

            // 게시물 수집 (최근 게시물 섹션)
            const posts = await page.evaluate(() => {
                const results = [];
                const articles = document.querySelectorAll('article');
                
                articles.forEach((article, index) => {
                    if (index >= 12) return; // 최대 12개
                    
                    const imgEl = article.querySelector('img');
                    const altText = imgEl ? imgEl.alt : '';
                    
                    if (altText && altText.match(/팝업|pop.?up/i)) {
                        results.push({
                            text: altText,
                            image: imgEl ? imgEl.src : ''
                        });
                    }
                });

                return results;
            });

            // 결과 처리
            for (const post of posts) {
                const info = this.extractInfo(post.text);
                if (info) {
                    info.source = 'instagram';
                    info.image_url = post.image;
                    this.results.push(info);
                }
            }

        } catch (error) {
            console.error('인스타그램 크롤링 오류:', error);
        } finally {
            if (browser) await browser.close();
        }

        return this.results;
    }

    extractInfo(text) {
        const info = {};

        // 브랜드명 추출
        const brandPatterns = [
            /#([가-힣A-Za-z0-9]+)\s*팝업/,
            /@([a-zA-Z0-9_.]+)/,
            /([가-힣A-Za-z0-9]+)\s*[xX]\s*성수/
        ];

        for (const pattern of brandPatterns) {
            const match = text.match(pattern);
            if (match) {
                info.name = match[1] + ' 팝업스토어';
                break;
            }
        }

        // 성수동 관련 주소
        if (text.includes('성수') || text.includes('서울숲')) {
            info.address = '서울 성동구 성수동';
        }

        // 기간 추출 (간단한 패턴)
        const periodPattern = /(\d{1,2}[\.\/]\d{1,2})\s*[-~]\s*(\d{1,2}[\.\/]\d{1,2})/;
        const periodMatch = text.match(periodPattern);
        if (periodMatch) {
            info.period = periodMatch[0];
        }

        if (info.name) {
            info.confidence = 4; // 인스타그램은 비교적 신뢰도 높음
            info.raw_data = { hashtags: text };
            return info;
        }

        return null;
    }
}

// CLI 실행
if (require.main === module) {
    const crawler = new InstagramPopupCrawler();
    crawler.crawl().then(results => {
        console.log(JSON.stringify(results, null, 2));
    });
}

module.exports = InstagramPopupCrawler;
