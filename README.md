# 🧸 말랑이 가격비교 · 후기 사이트 (squishy-price-tracker)

말랑이(스퀴시) **단일 카테고리에 특화된** 가격비교 + 후기 모아보기 사이트입니다.
한 말랑이를 여러 쇼핑몰 가격으로 **비교**하고, **가격 변화를 추적**하며, **후기를 한곳에서** 봅니다.

## ✨ 주요 기능 (계획)

- **가격 비교** — 하나의 말랑이에 네이버·쿠팡·알리·테무 가격을 나란히
- **가격 추적** — 시간에 따른 최저가 변화를 그래프로
- **후기 모아보기** — 쇼핑몰별 후기를 한 상품 페이지에 집계
- **관리자 큐레이션** — 사람이 상품을 만들고 각 몰 상세 URL을 연결하면, 크롤러가 알아서 가격·후기 갱신

## 🧭 핵심 아키텍처 — 큐레이션 기반

검색 결과를 통째로 긁는 자동수집이 아니라, **사람이 메인 상품을 만들고 각 몰의 상품 상세 URL을 직접 연결 → 크롤러는 등록된 URL만 주기적으로 갱신**합니다.

```
[관리자]  메인 상품 생성 → 각 몰 상세 URL 연결 (네이버·쿠팡·알리·테무)
   ↓ (사람이 매칭을 확정 → 자동 매칭 로직 불필요)
[크롤러]  등록된 URL만 주기 방문 → 가격·후기·재고 갱신 → 이력 적재
   ↓
[사용자]  상품별 몰 가격비교 + 가격 그래프 + 후기
```

이 방식의 이점: ① 검색을 안 두드려 **봇차단 위험 감소** ② 사람이 연결하니 **상품 매칭 오탐 0** ③ 인기 판별의 어려움을 **큐레이션으로 회피**.

## 🛠 기술 스택

| 영역 | 기술 |
|------|------|
| 웹 | CodeIgniter 3 (PHP, 서버사이드 뷰) |
| 프론트 | Bootstrap 5 · Alpine.js · Chart.js (CDN, 빌드 없음) |
| 크롤러 | Python · Playwright |
| DB | MySQL 8 (웹·크롤러 **공유 DB**) |
| 환경 | 하이브리드 — MySQL+PHP는 Docker, Python은 네이티브 venv |

> 웹과 크롤러는 코드로 직접 통신하지 않고 **공유 MySQL**로만 연결됩니다. (Python=쓰는 쪽, CI3=읽는 쪽)

## 📂 디렉토리 구조

```
squishy-price-tracker/
├─ docker-compose.yml     # db(mysql:8) + web(php:7.4-apache)
├─ docker/php/Dockerfile  # php + pdo_mysql + mod_rewrite
├─ web/                   # CodeIgniter 3 (웹앱)
└─ crawler/               # Python 크롤러 (Playwright)
```

## 🚀 개발 환경 실행

**요구사항**: Docker + Docker Compose, Python 3.12

```bash
# 1) 환경변수 준비
cp .env.example .env

# 2) 웹 + DB 기동 (http://localhost:8080)
docker compose up -d

# 3) Python 크롤러 환경
cd crawler
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
playwright install chromium
python hello.py
```

**DB 접속 컨텍스트**: CI3(web 컨테이너)는 호스트명 `db`, Python 크롤러(호스트)는 `127.0.0.1`로 3306에 접속합니다.

## 🗄 데이터 모델

- `products` — 메인 말랑이 (사람이 생성)
- `offers` — 상품에 연결된 몰별 링크/가격 (URL은 사람, 나머지는 크롤러)
- `price_history` — 가격 이력 (그래프 소스)
- `reviews` — 몰별 후기

## 🗺 로드맵

- [x] 개발 환경 세팅 (Docker + CI3 + Python, Hello World)
- [ ] DB 마이그레이션 (products / offers / price_history / reviews)
- [ ] 관리자 페이지 (상품 생성 + 몰 URL 연결, 자동채움)
- [ ] 크롤러 (네이버 → 쿠팡 → 알리 → 테무), cron 주기 실행
- [ ] 사용자 화면 (목록 + 상세: 가격비교표 · 가격 그래프 · 후기)
- [ ] 후속: 태그 분류, 후기 AI 요약(Claude), SNS 인기 크롤링, 최저가 알림

## ⚠️ 주의

스크래핑·후기 재게시는 각 사이트 약관·저작권 이슈가 있어, **개인/학습용 범위**로 시작합니다. 상용화 시 별도 검토가 필요합니다.
