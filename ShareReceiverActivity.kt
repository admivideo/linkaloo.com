package com.android.linkaloo

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
        private const val EXTRA_ALREADY_REDIRECTED = "com.android.linkaloo.EXTRA_ALREADY_REDIRECTED"
        private const val LINKALOO_DEEP_LINK_TARGET = "//linkaloo.com/agregar_favolink.php"
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        when (intent?.action) {
            Intent.ACTION_SEND -> {
                val sharedText = intent.getStringExtra(Intent.EXTRA_TEXT)
                if (!sharedText.isNullOrBlank()) {
                    handleLink(sharedText, alreadyRedirected = false)
                } else {
                    val stream = intent.getParcelableExtra<Uri>(Intent.EXTRA_STREAM)
                    stream?.toString()?.let { handleLink(it, alreadyRedirected = false) }
                }
            }
            Intent.ACTION_VIEW -> {
                val data: Uri? = intent.data
                val alreadyRedirected = intent.getBooleanExtra(EXTRA_ALREADY_REDIRECTED, false)
                data?.toString()?.let { handleLink(it, alreadyRedirected) }
            }
        }

        // Redirige a tu Main si procede o muestra una UI ligera
        finish()
    }

    private fun handleLink(link: String, alreadyRedirected: Boolean) {
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
            openInBrowser(sharedUri, alreadyRedirected)
            return
        }

        Log.d("ShareReceiver", "Forwarding shared link: $sharedUrl")
        val targetUri = Uri.parse("https:$LINKALOO_DEEP_LINK_TARGET").buildUpon()
            .appendQueryParameter("shared", sharedUrl)
            .build()
        openInBrowser(targetUri, alreadyRedirected = false)
    }

    private fun openInBrowser(uri: Uri, alreadyRedirected: Boolean) {
        val viewIntent = Intent(Intent.ACTION_VIEW, uri).addCategory(Intent.CATEGORY_BROWSABLE)

        if (!alreadyRedirected) {
            viewIntent.putExtra(EXTRA_ALREADY_REDIRECTED, true)
            startActivity(viewIntent)
            return
        }

        val alternatives = packageManager.queryIntentActivities(viewIntent, PackageManager.MATCH_DEFAULT_ONLY)
        val nonSelf = alternatives.firstOrNull { it.activityInfo.packageName != packageName }
        if (nonSelf != null) {
            val explicit = Intent(viewIntent).apply {
                setClassName(nonSelf.activityInfo.packageName, nonSelf.activityInfo.name)
            }
            startActivity(explicit)
        } else {
            startActivity(viewIntent)
        }
    }
}
