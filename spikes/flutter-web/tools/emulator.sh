#!/usr/bin/env bash
# WP-0.5: boot a headless Android emulator (KVM) for the diacritics screenshot comparison and
# install the spike APK. Stops with tools/stop.sh (pid file in server/var).
#
#   bash spikes/flutter-web/tools/emulator.sh
set -euo pipefail

here="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
var="$here/server/var"
export JAVA_HOME="${JAVA_HOME:-$HOME/tools/jdk-21}"
export ANDROID_HOME="${ANDROID_HOME:-$HOME/tools/android-sdk}"
export PATH="$JAVA_HOME/bin:$ANDROID_HOME/platform-tools:$ANDROID_HOME/emulator:$PATH"
image="system-images;android-35;google_apis;x86_64"

if ! "$ANDROID_HOME/cmdline-tools/latest/bin/avdmanager" list avd 2>/dev/null | grep -q "Name: sura-spike"; then
  echo no | "$ANDROID_HOME/cmdline-tools/latest/bin/avdmanager" create avd -n sura-spike -k "$image" -d pixel_6 --force >/dev/null
fi
nohup emulator -avd sura-spike -no-window -no-audio -no-boot-anim -no-snapshot -gpu swiftshader_indirect \
  >"$var/emulator.log" 2>&1 &
echo $! >"$var/emulator.pid"
adb wait-for-device
until [[ "$(adb shell getprop sys.boot_completed 2>/dev/null | tr -d '\r')" == "1" ]]; do sleep 2; done
adb install -r "$here/app/build/app/outputs/flutter-apk/app-x86_64-release.apk" >/dev/null
echo "emulator ready, app installed"
