"""환경 세팅 확인용 hello world 스크립트."""


def main() -> None:
    print("hello world — 말랑이 크롤러 준비 완료 🧸")

    # 설치된 핵심 의존성이 import 되는지만 가볍게 확인
    try:
        import playwright  # noqa: F401

        print("  ✓ playwright import OK")
    except ImportError:
        print("  ✗ playwright 미설치 (pip install -r requirements.txt 필요)")


if __name__ == "__main__":
    main()
