package com.android.linkaloo

<<<<<<< HEAD
import android.content.ActivityNotFoundException
import android.content.ClipData
import android.content.ComponentName
=======
>>>>>>> parent of ad2cf33 (Merge pull request #211 from admivideo/codex/revisar-androidmanifest-y-completar-flujo-de-trabajo)
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Bundle
import android.util.Log
import android.util.Patterns
import androidx.appcompat.app.AppCompatActivity
import java.util.Locale

class ShareReceiverActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        when (intent?.action) {
            Intent.ACTION_SEND -> {
                when (intent.type) {
                    "text/plain" -> {
                        val sharedText = intent.getStringExtra(Intent.EXTRA_TEXT)
                        sharedText?.let { handleLink(it) }
                    }
                    else -> {
                        val stream = intent.getParcelableExtra<Uri>(Intent.EXTRA_STREAM)
                        stream?.toString()?.let { handleLink(it) }
                    }
                }
            }
            Intent.ACTION_VIEW -> {
                val data: Uri? = intent.data
                data?.toString()?.let { handleLink(it) }
            }
        }

        // Redirige a tu Main si procede o muestra una UI ligera
        finish()
    }

    private fun handleLink(link: String) {
        val matcher = Patterns.WEB_URL.matcher(link)
        if (!matcher.find()) {
            Log.w("ShareReceiver", "No valid URL found in shared content")
            return
        }

        var sharedUrl = link.substring(matcher.start(), matcher.end()).trim()
        if (!sharedUrl.startsWith("http://", ignoreCase = true) &&
            !sharedUrl.startsWith("https://", ignoreCase = true)
        ) {
            sharedUrl = "https://$sharedUrl"
        }

        val sharedUri = Uri.parse(sharedUrl)
        val host = sharedUri.host?.lowercase(Locale.ROOT)

        if (host != null && (host == "linkaloo.com" || host.endsWith(".linkaloo.com"))) {
            Log.d("ShareReceiver", "Opening Linkaloo URL directly: $sharedUrl")
            startActivity(Intent(Intent.ACTION_VIEW, sharedUri))
            return
        }

        Log.d("ShareReceiver", "Forwarding shared link: $sharedUrl")
        val targetUri = Uri.parse("https://linkaloo.com/panel.php").buildUpon()
            .appendQueryParameter("shared", sharedUrl)
            .build()
<<<<<<< HEAD
        startActivityIfPossible(Intent(Intent.ACTION_VIEW, targetUri))
    }

    private fun extractFromClipData(clipData: ClipData?): String? {
        clipData ?: return null
        for (index in 0 until clipData.itemCount) {
            val item = clipData.getItemAt(index)
            val text = item.text?.toString()
            if (!text.isNullOrBlank()) {
                return text
            }
            val uriText = item.uri?.toString()
            if (!uriText.isNullOrBlank()) {
                return uriText
            }
        }
        return null
    }

    private fun startActivityIfPossible(intent: Intent) {
        val resolved = intent.resolveActivity(packageManager)
        if (resolved == null) {
            Log.e("ShareReceiver", "No activity available to handle intent: $intent")
            return
        }

        if (resolved.packageName == packageName &&
            resolved.className == ShareReceiverActivity::class.java.name
        ) {
            val browserIntent = Intent(intent).apply {
                addCategory(Intent.CATEGORY_BROWSABLE)
                setComponent(null)
                `package` = null
            }

            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
                val chooserIntent = Intent.createChooser(browserIntent, null).apply {
                    putExtra(
                        Intent.EXTRA_EXCLUDE_COMPONENTS,
                        arrayOf(ComponentName(this@ShareReceiverActivity, ShareReceiverActivity::class.java))
                    )
                }
                try {
                    startActivity(chooserIntent)
                } catch (error: ActivityNotFoundException) {
                    Log.e("ShareReceiver", "No external browser available for intent: $browserIntent", error)
                }
            } else {
                val alternatives = packageManager.queryIntentActivities(
                    browserIntent,
                    PackageManager.MATCH_DEFAULT_ONLY
                )
                val target = alternatives.firstOrNull {
                    it.activityInfo.packageName != packageName ||
                        it.activityInfo.name != ShareReceiverActivity::class.java.name
                }

                if (target != null) {
                    browserIntent.setClassName(target.activityInfo.packageName, target.activityInfo.name)
                    startActivity(browserIntent)
                } else {
                    Log.e("ShareReceiver", "No external browser available for intent: $browserIntent")
                }
            }
            return
        }

        startActivity(intent)
    }

    private fun Intent.getParcelableUriExtra(name: String): Uri? {
        return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            getParcelableExtra(name, Uri::class.java)
        } else {
            @Suppress("DEPRECATION")
            getParcelableExtra(name)
        }
    }

    private fun Intent.getParcelableUriArrayListExtra(name: String): List<Uri>? {
        return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            getParcelableArrayListExtra(name, Uri::class.java)
        } else {
            @Suppress("DEPRECATION")
            getParcelableArrayListExtra(name)
        }
=======
        startActivity(Intent(Intent.ACTION_VIEW, targetUri))
>>>>>>> parent of ad2cf33 (Merge pull request #211 from admivideo/codex/revisar-androidmanifest-y-completar-flujo-de-trabajo)
    }
}
