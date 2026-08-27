ARG GO2RTC_VERSION=1.9.14

FROM golang:1.25-alpine AS build
ARG GO2RTC_VERSION

RUN apk add --no-cache git
WORKDIR /build
RUN git clone --branch "v${GO2RTC_VERSION}" --depth 1 https://github.com/AlexxIT/go2rtc.git .
COPY cloudless-tapo.patch /tmp/cloudless-tapo.patch
RUN git apply /tmp/cloudless-tapo.patch
RUN CGO_ENABLED=0 go build -ldflags "-s -w" -trimpath

FROM alexxit/go2rtc:${GO2RTC_VERSION}
COPY --from=build /build/go2rtc /usr/local/bin/go2rtc
