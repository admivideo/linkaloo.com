package com.android.linkaloo

import android.content.ClipData
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.util.Log
import android.util.Patterns
import androidx.appcompat.app.AppCompatActivity
import java.util.Locale

class ShareReceiverActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        when (intent?.action) {
            Intent.ACTION_SEND -> handleSendIntent(intent)
            Intent.ACTION_SEND_MULTIPLE -> handleSendMultipleIntent(intent)
            Intent.ACTION_VIEW -> handleViewIntent(intent)
        }

        // Redirige a tu Main si procede o muestra una UI ligera
        finish()
    }

    private fun handleSendIntent(intent: Intent) {
        val type = intent.type.orEmpty()
        if (type.startsWith("text/")) {
            val sharedText = intent.getCharSequenceExtra(Intent.EXTRA_TEXT)?.toString()
            if (!sharedText.isNullOrBlank()) {
                handleLink(sharedText)
                return
            }

            extractFromClipData(intent.clipData)?.let {
                handleLink(it)
                return
            }
        }

        val stream = intent.getParcelableUriExtra(Intent.EXTRA_STREAM)
        if (stream != null) {
            handleLink(stream.toString())
            return
        }

        Log.w("ShareReceiver", "No shareable content found in ACTION_SEND intent")
    }

    private fun handleSendMultipleIntent(intent: Intent) {
        val texts = intent.getCharSequenceArrayListExtra(Intent.EXTRA_TEXT)
        texts?.firstOrNull { it.isNotBlank() }?.toString()?.let {
            handleLink(it)
            return
        }

        extractFromClipData(intent.clipData)?.let {
            handleLink(it)
            return
        }

        val streams = intent.getParcelableUriArrayListExtra(Intent.EXTRA_STREAM)
        streams?.firstOrNull()?.let {
            handleLink(it.toString())
            return
        }

        Log.w("ShareReceiver", "No shareable content found in ACTION_SEND_MULTIPLE intent")
    }

    private fun handleViewIntent(intent: Intent) {
        val data: Uri? = intent.data
        if (data != null) {
            handleLink(data.toString())
        } else {
            Log.w("ShareReceiver", "ACTION_VIEW intent received without data")
        }
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
            startActivityIfPossible(Intent(Intent.ACTION_VIEW, sharedUri))
            return
        }

        Log.d("ShareReceiver", "Forwarding shared link: $sharedUrl")
        val targetUri = Uri.parse("https://linkaloo.com/panel.php").buildUpon()
            .appendQueryParameter("shared", sharedUrl)
            .build()
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
        if (resolved != null) {
            startActivity(intent)
        } else {
            Log.e("ShareReceiver", "No activity available to handle intent: $intent")
        }
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
    }
}
