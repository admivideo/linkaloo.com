package com.android.linkaloo

import android.content.Intent
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
        startActivity(Intent(Intent.ACTION_VIEW, targetUri))
    }
}
