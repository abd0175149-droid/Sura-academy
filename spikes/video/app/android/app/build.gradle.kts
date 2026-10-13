plugins {
    id("com.android.application")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

android {
    namespace = "academy.sura.spike.sura_video_spike"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = flutter.ndkVersion

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    defaultConfig {
        // TODO: Specify your own unique Application ID (https://developer.android.com/studio/build/application-id.html).
        applicationId = "academy.sura.spike.sura_video_spike"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        // flutter_zoom_videosdk needs Android 9 (API 28); livekit_client needs 21.
        minSdk = 28
        targetSdk = flutter.targetSdkVersion
        // Uses the version code from pubspec.yaml. When using split APKs, 1000 * ABI_VERSION
        // is added automatically by Flutter. (https://developer.android.com/studio/build/configure-apk-splits#configure-APK-versions)
        // You can force using the value of versionCode by specifying the `-P force-version-code-ignoring-abi=true`
        // flag during build.
        versionCode = flutter.versionCode
        versionName = flutter.versionName
    }

    buildTypes {
        release {
            // Spike APK for side-loading on the owner's phone: debug-key signed, never published.
            signingConfig = signingConfigs.getByName("debug")
            // WP-0.4 finding: R8 fails on classes the Zoom SDK and its Tink dependency reference but
            // do not ship (javax.annotation.*, javax.lang.model.*). A product build would need
            // -dontwarn and keep rules for the Zoom SDK; the spike skips shrinking instead.
            isMinifyEnabled = false
            isShrinkResources = false
        }
    }
}

kotlin {
    compilerOptions {
        jvmTarget = org.jetbrains.kotlin.gradle.dsl.JvmTarget.JVM_17
    }
}

flutter {
    source = "../.."
}

dependencies {
    // flutter_zoom_videosdk 2.6.0 declares the Zoom SDK as `implementation`, but its plugin class
    // implements us.zoom.sdk.ZoomVideoSDKDelegate, so the generated plugin registrant in this
    // module cannot compile without the SDK on its own classpath (WP-0.4 finding).
    implementation("us.zoom.videosdk:zoomvideosdk-core:2.6.0")
}
