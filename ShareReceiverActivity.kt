package com.linka2025.linkaloo

import android.content.ActivityNotFoundException
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Bundle
import android.util.Log
import android.util.Patterns
import androidx.appcompat.app.AppCompatActivity
import java.util.Locale

class ShareReceiverActivity : AppCompatActivity() {

    private companion object {
        private val LINKALOO_DEEP_LINK_BASE = Uri.parse("linkaloo://linkaloo.com/agregar_favolink.php")
        private val LINKALOO_WEB_FALLBACK = Uri.parse("https://linkaloo.com/agregar_favolink.php")
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        when (intent?.action) {
            Intent.ACTION_SEND -> {
                val sharedText = intent.getStringExtra(Intent.EXTRA_TEXT)
                if (!sharedText.isNullOrBlank()) {
                    handleLink(sharedText)
                } else {
                    val stream = intent.getParcelableExtra<Uri>(Intent.EXTRA_STREAM)
                    stream?.toString()?.let { handleLink(it) }
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
        val trimmed = link.trim()
        if (trimmed.isEmpty()) {
            Log.w("ShareReceiver", "Empty link received")
            return
        }

        val incomingUri = runCatching { Uri.parse(trimmed) }.getOrNull()
        if (incomingUri?.scheme?.equals("linkaloo", ignoreCase = true) == true) {
            val normalized = normalizeLinkalooUri(incomingUri)
            val fallback = normalized.buildUpon().scheme("https").build()
            Log.d("ShareReceiver", "Handling Linkaloo deep link: $normalized")
            openUri(normalized, fallback)
            return
        }

        val matcher = Patterns.WEB_URL.matcher(trimmed)
        if (!matcher.find()) {
            Log.w("ShareReceiver", "No valid URL found in shared content")
            return
        }

        var sharedUrl = trimmed.substring(matcher.start(), matcher.end()).trim()
        if (!sharedUrl.startsWith("http://", ignoreCase = true) &&
            !sharedUrl.startsWith("https://", ignoreCase = true)
        ) {
            sharedUrl = "https://$sharedUrl"
        }

        val sharedUri = Uri.parse(sharedUrl)
        val host = sharedUri.host?.lowercase(Locale.ROOT)

        if (host != null && (host == "linkaloo.com" || host.endsWith(".linkaloo.com"))) {
            Log.d("ShareReceiver", "Opening Linkaloo URL directly: $sharedUrl")
            openUri(sharedUri, null)
            return
        }

        Log.d("ShareReceiver", "Forwarding shared link via deep link: $sharedUrl")
        val targetUri = LINKALOO_DEEP_LINK_BASE.buildUpon()
            .appendQueryParameter("shared", sharedUrl)
            .build()
        val fallbackUri = LINKALOO_WEB_FALLBACK.buildUpon()
            .appendQueryParameter("shared", sharedUrl)
            .build()
        openUri(targetUri, fallbackUri)
    }

    private fun normalizeLinkalooUri(uri: Uri): Uri {
        val host = uri.host
        val authority = when {
            host.isNullOrBlank() || !host.contains('.') -> "linkaloo.com"
            else -> host
        }

        val builder = Uri.Builder()
            .scheme(uri.scheme ?: "linkaloo")
            .authority(
                if (uri.port != -1) {
                    "$authority:${uri.port}"
                } else {
                    authority
                }
            )

        if (host.isNullOrBlank() || !host.contains('.')) {
            if (!host.isNullOrBlank()) {
                builder.appendPath(host)
            }
        }

        uri.pathSegments.filter { it.isNotEmpty() }.forEach { builder.appendPath(it) }

        uri.encodedQuery?.let { builder.encodedQuery(it) }
        uri.fragment?.let { builder.fragment(it) }
        return builder.build()
    }

    private fun openUri(primary: Uri, fallback: Uri?) {
        val viewIntent = Intent(Intent.ACTION_VIEW, primary).addCategory(Intent.CATEGORY_BROWSABLE)
        val alternatives = packageManager.queryIntentActivities(viewIntent, PackageManager.MATCH_DEFAULT_ONLY)

        val nonSelf = alternatives.firstOrNull { it.activityInfo.packageName != packageName }
        if (nonSelf != null) {
            val explicit = Intent(viewIntent).apply {
                setClassName(nonSelf.activityInfo.packageName, nonSelf.activityInfo.name)
            }
            startActivity(explicit)
            return
        }

        val handledBySelf = alternatives.any { it.activityInfo.packageName == packageName }
        if (handledBySelf) {
            if (fallback != null && fallback != primary) {
                openUri(fallback, null)
            } else {
                Log.w("ShareReceiver", "No external handler for $primary and no fallback available")
            }
            return
        }

        try {
            startActivity(viewIntent)
        } catch (ex: ActivityNotFoundException) {
            if (fallback != null && fallback != primary) {
                openUri(fallback, null)
            } else {
                Log.e("ShareReceiver", "Unable to open $primary", ex)
            }
        }
    }
}
