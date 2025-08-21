/**
 * 네이버 이미지 크롤러
 * 
 * 네이버 검색에서 이미지를 수집하는 Node.js 크롤러
 */

const puppeteer = require('puppeteer');

async function crawlNaverImages(query, maxImages = 5) {
    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    try {
        const page = await browser.newPage();
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        // 네이버 이미지 검색
        const searchUrl = `https://search.naver.com/search.naver?where=image&query=${encodeURIComponent(query)}`;
        await page.goto(searchUrl, { waitUntil: 'networkidle2' });
        
        // 이미지 URL 수집
        const images = await page.evaluate((limit) => {
            const imgs = [];
            const elements = document.querySelectorAll('._image._listImage');
            
            for (let i = 0; i < Math.min(elements.length, limit); i++) {
                const img = elements[i];
                const src = img.getAttribute('src') || img.getAttribute('data-source');
                if (src && src.startsWith('http')) {
                    imgs.push(src.replace(/\?.*$/, '')); // 쿼리 파라미터 제거
                }
            }
            
            return imgs;
        }, maxImages);
        
        console.log(JSON.stringify({ success: true, images }));
        
    } catch (error) {
        console.error(JSON.stringify({ success: false, error: error.message }));
    } finally {
        await browser.close();
    }
}

// 커맨드라인 인자 처리
const args = process.argv.slice(2);
const query = args[0];

if (!query) {
    console.error(JSON.stringify({ success: false, error: 'Query parameter is required' }));
    process.exit(1);
}

crawlNaverImages(query);
