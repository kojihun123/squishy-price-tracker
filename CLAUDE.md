# 말랑이(스퀴시) 가격비교 · 후기 사이트

> 말랑이(스퀴시) **단일 카테고리 특화** 가격비교 + 후기 사이트.
> 한 말랑이를 여러 쇼핑몰 가격으로 **비교**하고, **가격 변화를 추적**하며, **후기를 모아본다**.

## 핵심 아키텍처 — 큐레이션 기반 (검색 자동발견 ❌)

"말랑이 검색결과를 통째로 긁는" 자동발견이 아니라, **사람이 메인 상품을 만들고 각 몰의 상품 상세 URL을 직접 연결 → 크롤러는 등록된 URL만 주기적으로 긁어 가격·후기를 갱신**한다.

```
[관리자]  메인 상품 생성 → 각 몰 상세 URL 직접 연결 (네이버·쿠팡·알리·테무)
   ↓ (사람이 매칭을 확정하므로 자동 매칭 로직 불필요)
[크롤러]  등록된 URL만 주기 방문 → 가격·후기·재고 갱신 → price_history 적재
   ↓
[사용자]  상품별 몰 가격비교 + 가격 그래프 + 후기
```

이 방식의 이점:
1. 검색·페이지네이션을 안 두드려 **봇차단 리스크 대폭 감소**
2. 사람이 연결하니 **상품 매칭 오탐 0** (매칭 로직 자체가 불필요)
3. 인기 자동판별의 어려움을 **큐레이션으로 회피** (인기 발굴은 후속 SNS 크롤링으로)

**MVP 범위**: 인기 말랑이 20~30개를 손으로 골라 시작. 돌아가는 사이트를 먼저 만든다.

## 기술 스택

| 영역 | 선택 | 비고 |
|------|------|------|
| 웹 | **CodeIgniter 3** (PHP, 서버사이드 뷰) | `web/` |
| 프론트 | **Bootstrap 5 + Alpine.js + Chart.js** | 전부 CDN, 빌드 스텝 없음 |
| 크롤러 | **Python + Playwright** | `crawler/`, 네이티브 venv |
| DB | **MySQL 8** | 웹·크롤러 **공유 DB**로 연결 (직접 통신 X) |
| 환경 | **하이브리드** | MySQL+PHP는 Docker, Python은 호스트 네이티브 |

**웹 ↔ 크롤러는 코드로 직접 통신하지 않는다.** 공유 MySQL이 유일한 접점 — Python은 쓰는 쪽, CI3는 읽는 쪽.

## 디렉토리 구조

```
squishy-price-tracker/
├─ CLAUDE.md                 # 이 문서
├─ docker-compose.yml        # db(mysql:8) + web(php:7.4-apache)
├─ docker/php/Dockerfile     # php:7.4-apache + pdo_mysql + mod_rewrite
├─ .env.example              # DB 접속 정보 (web/crawler 공통 값)
├─ web/                      # CodeIgniter 3 (application/ + system/ + index.php)
│  └─ application/
│     ├─ controllers/        # Home, Product, Admin
│     ├─ models/             # Product_model, Offer_model, Price_history_model, Review_model
│     ├─ views/              # layout, home, product, admin/*
│     ├─ migrations/         # products/offers/price_history/reviews
│     └─ config/             # routes, database, ...
└─ crawler/                  # Python
   ├─ scrapers/              # base, naver, coupang, ali, temu
   ├─ db.py                  # 공유 DB 접속 (PyMySQL/SQLAlchemy)
   ├─ pipeline.py            # offer 갱신 + price_history 스냅샷 + reviews 저장
   ├─ run.py                 # CLI: python run.py [--source naver] [--fill URL]
   └─ requirements.txt
```

## 데이터 모델 (MySQL)

- **`products`** — 사람이 만드는 메인 말랑이: `id, name, brand, image_url, is_published, created_at, updated_at`
- **`offers`** — 상품에 연결된 몰별 링크(사람이 URL 등록, 크롤러가 나머지 갱신): `id, product_id(FK), source, url, mall_name, price, review_count, rating, in_stock, last_crawled_at, created_at` · unique(`source`,`url`)
- **`price_history`** — 가격 이력: `id, offer_id(FK), price, recorded_at` · index(`offer_id`,`recorded_at`)
- **`reviews`** — 후기(몰별): `id, offer_id(FK), rating, content, author, reviewed_at, created_at`

> 후속: `tags`/`product_tags`(종류 분류), `offers.review_summary`(AI 후기 요약)

## 크롤러 설계

- **인터페이스** (`scrapers/base.py`): `detail(url) → {title, image, price, review_count, rating, in_stock, ...}`, `reviews(url) → [review]` — **검색(search) 불필요**
- **실행** (`run.py`): DB에서 대상 offer 조회 → 사이트별 파서로 `detail`/`reviews` 수집 → `pipeline`이 offer 갱신 + `price_history` 스냅샷 + `reviews` 저장
- **자동채움**: 관리자가 URL 등록 시 `detail(url)`을 즉시 호출해 상품 폼(제목·이미지·가격) 자동 채움
- **안전**: 요청 간 랜덤 딜레이·User-Agent·낮은 동시성. 캡차·실패 시 스킵/재시도. 지정 URL만 돌아 부하·차단 위험 낮음
- **주기**: cron 일 1~2회. 가격 변동 시 `price_history` 적재(그래프 소스)

## 개발 환경 실행

**사전 요구**: Docker + Docker Compose, Python 3.12

```bash
# 1) DB + 웹서버 기동
docker compose up -d          # db(mysql:8, :3306) + web(php:7.4-apache, :8080)
#   → http://localhost:8080  에서 CI3 페이지 확인

# 2) Python 크롤러 환경 (호스트 네이티브)
cd crawler
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
playwright install --with-deps chromium
python hello.py
```

**DB 접속 컨텍스트 주의**:
- CI3(web 컨테이너) → 호스트명 **`db`**:3306 (compose 서비스명)
- Python 크롤러(호스트) → **`127.0.0.1`**:3306 (포트 매핑)

## 📌 진행 상황 & 다음 시작점 (RESUME)

**완료된 것**
- ✅ 개발환경 세팅 (Docker: MySQL8 + PHP7.4/CI3, Python venv) — Hello World
- ✅ **첫 세로 슬라이스 "DB→화면" 완성** (CI3 실전 학습 겸)
  - CI3 ↔ MySQL 연결: `web/application/config/database.php`(hostname=`db`, utf8mb4), `autoload.php`에 `database` 라이브러리 등록
  - `products` 테이블 + 샘플 3건: `db/schema.sql` (utf8mb4). *실행법: `docker compose exec -T db mysql --default-character-set=utf8mb4 -u squishy -psquishypass squishy < db/schema.sql`*
  - `models/Product_model.php` (`get_all()` → Query Builder)
  - `controllers/Product.php` + `views/products.php` (목록 표)
  - **깔끔한 URL**: `config.php` `index_page=''` + `web/.htaccess` → `http://localhost:8080/product`

**▶ 다음에 여기서 시작** (추천 순서)
1. **상품 상세 페이지** — `/product/5` 처럼 URL에 ID 받아 개별 상품 보기 (`Product::view($id)` + 모델 `get_by_id()`)
2. **관리자 페이지** — 폼으로 상품 추가 (`$this->input->post()` + 모델 INSERT)
3. **`offers` 테이블 + 몰 URL 연결** — 상품에 쇼핑몰 상세 URL 붙이기 (데이터 모델 참고)
4. 크롤러(네이버 `detail`/`reviews`) → 가격/후기 갱신 → 사용자 상세에 가격비교·그래프
5. 쿠팡·알리·테무 파서 + cron

> 학습 방식: **사용자가 직접 타이핑**, 조수는 설명·검토·디버깅. (뷰/`.htaccess`/SQL 등은 요청 시 조수가 작성)

**후속**: 태그(종류 분류), 후기 AI 요약(Claude), SNS 인기 크롤링, 최저가 알림.

## 리스크 / 주의

- **파서 유지보수**: 각 몰 HTML 변경 시 사이트별 파서 수정 (사이트별 격리로 영향 최소화)
- **봇차단(잔존)**: 지정 URL만 돌아 위험 낮지만 쿠팡/테무/알리는 캡차 가능
- **법적/약관**: 스크래핑·후기 재게시는 약관·저작권 이슈 → 개인/학습용 범위로 시작
